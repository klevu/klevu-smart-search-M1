<?php

/**
 * Class Klevu_Search_Model_Product_Sync
 * @method Varien_Db_Adapter_Interface getConnection()
 * @method Mage_Core_Model_Store getStore()
 * @method string getSessionId()
 */
class Klevu_Search_Model_Product_Sync extends Klevu_Search_Model_Sync {

    /**
     * It has been determined during development that Product Sync uses around
     * 120kB of memory for each product it syncs, or around 10MB of memory for
     * each 100 product page.
     */
    const RECORDS_PER_PAGE = 100;

    const NOTIFICATION_GLOBAL_TYPE = "product_sync";
    const NOTIFICATION_STORE_TYPE_PREFIX = "product_sync_store_";
    protected $_klevu_features_response;

    public function _construct() {
        parent::_construct();

        $this->addData(array(
            'connection' => Mage::getModel('core/resource')->getConnection("core_write")
        ));
    }

    public function getJobCode() {
        return "klevu_search_product_sync";
    }

    /**
     * Perform Product Sync on any configured stores, adding new products, updating modified and
     * deleting removed products since last sync.
     */
    public function run() {
        try {
			
			// reset the flag for fail message
			Mage::getSingleton('core/session')->setKlevuFailedFlag(0);
			
			// check the status of indexing when collection method selected to sync data 
			$config = Mage::helper('klevu_search/config');
			if($config->getCollectionMethod()) {
				if(Mage::helper('klevu_search')->getStatuOfIndexing()) {
					$this->notify(Mage::helper('klevu_search')->__("Product sync failed:One of your Magento indexes is not up-to-date.  Please, rebuild your indexes (see System > Index Management)."),null);
					Mage::helper('klevu_search')->log(Zend_Log::INFO, "Product sync failed:One of your Magento indexes is not up-to-date.  Please, rebuild your indexes (see System > Index Management).");

					return true;
				}
			}
			
            /* mark for update special price product */
            $this->markProductForUpdate();
            
            /* update boosting rule event */
            try {
                Mage::helper('klevu_search')->log(Zend_Log::INFO, "Boosting rule update is started");
                Mage::dispatchEvent('update_rule_of_products', array());
            } catch(Exception $e) {
                Mage::helper('klevu_search')->log(Zend_Log::WARN, "Unable to update boosting rule");

            }
            
            // Sync Data only for selected store from config wizard
            $firstSync = Mage::getSingleton('klevu_search/session')->getFirstSync();

            if(!empty($firstSync)){
                /** @var Mage_Core_Model_Store $store */
                $this->reset();
                $onestore = Mage::app()->getStore($firstSync);
                if (!$this->setupSession($onestore)) {
                    return;
                }
                
                $this->syncData($onestore);
                return;
            }
            
            if ($this->isRunning(2)) {
                // Stop if another copy is already running
                $this->log(Zend_Log::INFO, "Stopping because another copy is already running.");
                return;
            }
            
            $stores = Mage::app()->getStores();
            
            
            foreach ($stores as $store) {
                $this->reset();
                if (!$this->setupSession($store)) {
                    continue;
                }
                $this->syncData($store);
            }
            
            // update rating flag after all store view sync
            $rating_upgrade_flag = $config->getRatingUpgradeFlag();
            if($rating_upgrade_flag==0) {
                $config->saveRatingUpgradeFlag(1);
            }
        } catch (Exception $e) {
            // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
            Mage::helper('klevu_search')->log(Zend_Log::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
            throw $e;
        }
    }
    
    
    public function syncData($store){
                
                if ($this->rescheduleIfOutOfMemory()) {
                    return;
                }
                
                $config = Mage::helper('klevu_search/config');
                $session = Mage::getSingleton('klevu_search/session');
                $firstSync = $session->getFirstSync();
                try {
                    $rating_upgrade_flag = $config->getRatingUpgradeFlag();
                    if(!empty($firstSync) || $rating_upgrade_flag==0) {
                        $this->updateProductsRating($store);
                    }
                } catch(Exception $e) {
                    Mage::helper('klevu_search')->log(Zend_Log::WARN, sprintf("Unable to update rating attribute %s", $store->getName()));
                }
                //set current store so will get proper bundle price 
                Mage::app()->setCurrentStore($store->getId());
                
                $this->log(Zend_Log::INFO, sprintf("Starting sync for %s (%s).", $store->getWebsite()->getName(), $store->getName()));

                $actions = array(
                    'delete' => 
					$this->getConnection()
                        ->select()
                        ->union(array(
							$this->getConnection()
							->select()
							/*
							 * Select synced products in the current store/mode that are no longer enabled
							 * (don't exist in the products table, or have status disabled for the current
							 * store, or have status disabled for the default store) or are not visible
							 * (in the case of configurable products, check the parent visibility instead).
							 */
							->from(
								array('k' => $this->getTableName("klevu_search/product_sync")),
								array('product_id' => "k.product_id", 'parent_id' => "k.parent_id")
							)
							->joinLeft(
								array('v' => $this->getTableName("catalog/category_product_index")),
								"v.product_id = k.product_id AND v.store_id = :store_id",
								""
							)
							->joinLeft(
								array('p' => $this->getTableName("catalog/product")),
								"p.entity_id = k.product_id",
								""
							)
							->joinLeft(
								array('ss' => $this->getProductStatusAttribute()->getBackendTable()),
								"ss.attribute_id = :status_attribute_id AND ss.entity_id = k.product_id AND ss.store_id = :store_id",
								""
							)
							->joinLeft(
								array('sd' => $this->getProductStatusAttribute()->getBackendTable()),
								"sd.attribute_id = :status_attribute_id AND sd.entity_id = k.product_id AND sd.store_id = :default_store_id",
								""
							)
							->where("(k.store_id = :store_id) AND (k.type = :type) AND (k.test_mode = :test_mode) AND ((p.entity_id IS NULL) OR (CASE WHEN ss.value_id > 0 THEN ss.value ELSE sd.value END != :status_enabled) OR (CASE WHEN k.parent_id = 0 THEN k.product_id ELSE k.parent_id END NOT IN (?)) )",
								$this->getConnection()
									->select()
									->from(
										array('i' => $this->getTableName("catalog/category_product_index")),
										array('id' => "i.product_id")
									)
									->where("(i.store_id = :store_id) AND (i.visibility IN (:visible_both, :visible_search))")
								
							),
							$this->getConnection()
                                ->select()
                                /*
                                 * Select products which are not associated with parent 
                                 * but still parent exits in klevu product sync table with parent id
                                 * 
                                 */
                                ->from(
                                    array('ks' => $this->getTableName("klevu_search/product_sync")),
                                    array('product_id' => "ks.product_id","parent_id" => 'ks.parent_id')
                                )
								->where("(ks.parent_id !=0 AND ks.product_id NOT IN (?) AND ks.store_id = :store_id)",
									$this->getConnection()
									->select()
									/*
									 * Select products from catalog super link table
									 */
									->from(
										array('s' => $this->getTableName("catalog/product_super_link")),
										array('product_id' => "s.product_id")
									)
								)
							)
					    )		
                        ->group(array('k.product_id', 'k.parent_id'))
                        ->bind(array(
                            'type'          => "products",
                            'store_id'       => $store->getId(),
                            'default_store_id' => Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID,
                            'test_mode'      => $this->isTestModeEnabled(),
                            'status_attribute_id' => $this->getProductStatusAttribute()->getId(),
                            'status_enabled' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
                            'visible_both'   => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                            'visible_search' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH
                        )),

                    'update' => $this->getConnection()
                        ->select()
                        ->union(array(
                            // Select products without parents that need to be updated
                            $this->getConnection()
                                ->select()
                                /*
                                 * Select synced non-configurable products for the current store/mode
                                 * that are visible (using the category product index) and have been
                                 * updated since last sync.
                                 */
                                ->from(
                                    array('k' => $this->getTableName("klevu_search/product_sync")),
                                    array('product_id' => "k.product_id", 'parent_id' => "k.parent_id")
                                )
                                ->join(
                                    array('p' => $this->getTableName("catalog/product")),
                                    "p.entity_id = k.product_id",
                                    ""
                                )
                                ->join(
                                    array('i' => $this->getTableName("catalog/category_product_index")),
                                    "i.product_id = k.product_id AND k.store_id = i.store_id AND i.visibility IN (:visible_both, :visible_search)",
                                    ""
                                )
                                ->where("(k.store_id = :store_id) AND (k.type = :type) AND (k.test_mode = :test_mode) AND (p.type_id != :configurable) AND (p.updated_at > k.last_synced_at)"),
                            // Select products with parents (configurable) that need to be updated
                            $this->getConnection()
                                ->select()
                                /*
                                 * Select synced products for the current store/mode that are configurable
                                 * children (have entries in the super link table), are enabled for the current
                                 * store (or the default store), have visible parents (using the category product
                                 * index) and, either the product or the parent, have been updated since last sync.
                                 */
                                ->from(
                                    array('k' => $this->getTableName("klevu_search/product_sync")),
                                    array('product_id' => "k.product_id", 'parent_id' => "k.parent_id")
                                )
                                ->join(
                                    array('s' => $this->getTableName("catalog/product_super_link")),
                                    "k.parent_id = s.parent_id AND k.product_id = s.product_id",
                                    ""
                                )
                                ->join(
                                    array('i' => $this->getTableName("catalog/category_product_index")),
                                    "k.parent_id = i.product_id AND k.store_id = i.store_id AND i.visibility IN (:visible_both, :visible_search)",
                                    ""
                                )
                                ->join(
                                    array('p1' => $this->getTableName("catalog/product")),
                                    "k.product_id = p1.entity_id",
                                    ""
                                )
                                ->join(
                                    array('p2' => $this->getTableName("catalog/product")),
                                    "k.parent_id = p2.entity_id",
                                    ""
                                )
                                ->joinLeft(
                                    array('ss' => $this->getProductStatusAttribute()->getBackendTable()),
                                    "ss.attribute_id = :status_attribute_id AND ss.entity_id = k.product_id AND ss.store_id = :store_id",
                                    ""
                                )
                                ->joinLeft(
                                    array('sd' => $this->getProductStatusAttribute()->getBackendTable()),
                                    "sd.attribute_id = :status_attribute_id AND sd.entity_id = k.product_id AND sd.store_id = :default_store_id",
                                    ""
                                )
                                ->where("(k.store_id = :store_id) AND (k.type = :type) AND (k.test_mode = :test_mode) AND (CASE WHEN ss.value_id > 0 THEN ss.value ELSE sd.value END = :status_enabled) AND ((p1.updated_at > k.last_synced_at) OR (p2.updated_at > k.last_synced_at))")
                        ))
                        ->group(array('k.product_id', 'k.parent_id'))
                        ->bind(array(
                            'type'          => "products",
                            'store_id' => $store->getId(),
                            'default_store_id' => Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID,
                            'test_mode' => $this->isTestModeEnabled(),
                            'configurable' => Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE,
                            'visible_both' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                            'visible_search' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH,
                            'status_attribute_id' => $this->getProductStatusAttribute()->getId(),
                            'status_enabled' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
                        )),

                     'add' => $this->getConnection()
                        ->select()
                        ->union(array(
                            // Select non-configurable products that need to be added
                            $this->getConnection()
                                ->select()
                                /*
                                 * Select non-configurable products that are visible in the current
                                 * store (using the category product index), but have not been synced
                                 * for this store yet.
                                 */
                                ->from(
                                    array('p' => $this->getTableName("catalog/product")),
                                    array('product_id' => "p.entity_id", 'parent_id' => new Zend_Db_Expr("0"))
                                )
                                ->join(
                                    array('i' => $this->getTableName("catalog/category_product_index")),
                                    "p.entity_id = i.product_id AND i.store_id = :store_id AND i.visibility IN (:visible_both, :visible_search)",
                                    ""
                                )
                                ->joinLeft(
                                    array('k' => $this->getTableName("klevu_search/product_sync")),
                                    "p.entity_id = k.product_id AND k.parent_id = 0 AND i.store_id = k.store_id AND k.test_mode = :test_mode AND k.type = :type",
                                    ""
                                )
                                ->where("(p.type_id != :configurable) AND (k.product_id IS NULL)"),
                            // Select configurable parent & product pairs that need to be added
                            $this->getConnection()
                                ->select()
                                /*
                                 * Select configurable product children that are enabled (for the current
                                 * store or for the default store), have visible parents (using the category
                                 * product index) and have not been synced yet for the current store with
                                 * the current parent.
                                 */
                                ->from(
                                    array('s' => $this->getTableName("catalog/product_super_link")),
                                    array('product_id' => "s.product_id", 'parent_id' => "s.parent_id")
                                )
                                ->join(
                                    array('i' => $this->getTableName("catalog/category_product_index")),
                                    "s.parent_id = i.product_id AND i.store_id = :store_id AND i.visibility IN (:visible_both, :visible_search)",
                                    ""
                                )
                                ->joinLeft(
                                    array('ss' => $this->getProductStatusAttribute()->getBackendTable()),
                                    "ss.attribute_id = :status_attribute_id AND ss.entity_id = s.product_id AND ss.store_id = :store_id",
                                    ""
                                )
                                ->joinLeft(
                                    array('sd' => $this->getProductStatusAttribute()->getBackendTable()),
                                    "sd.attribute_id = :status_attribute_id AND sd.entity_id = s.product_id AND sd.store_id = :default_store_id",
                                    ""
                                )
                                ->joinLeft(
                                    array('k' => $this->getTableName("klevu_search/product_sync")),
                                    "s.parent_id = k.parent_id AND s.product_id = k.product_id AND k.store_id = :store_id AND k.test_mode = :test_mode AND k.type = :type",
                                    ""
                                )
                                ->where("(CASE WHEN ss.value_id > 0 THEN ss.value ELSE sd.value END = :status_enabled) AND (k.product_id IS NULL)")
                        ))
                        ->group(array('k.product_id', 'k.parent_id'))
                        ->bind(array(
                            'type' => "products",
                            'store_id' => $store->getId(),
                            'default_store_id' => Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID,
                            'test_mode' => $this->isTestModeEnabled(),
                            'configurable' => Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE,
                            'visible_both' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                            'visible_search' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH,
                            'status_attribute_id' => $this->getProductStatusAttribute()->getId(),
                            'status_enabled' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED
                        ))
						
                );

                $errors = 0;

                foreach ($actions as $action => $statement) {
                    if ($this->rescheduleIfOutOfMemory()) {
                        return;
                    }
                    $method = $action . "Products";
				
                    $products = $this->getConnection()->fetchAll($statement, $statement->getBind());
					

                    $total = count($products);
                    $this->log(Zend_Log::INFO, sprintf("Found %d products to %s.", $total, $action));
                    $pages = ceil($total / static::RECORDS_PER_PAGE);
                    for ($page = 1; $page <= $pages; $page++) {
                        if ($this->rescheduleIfOutOfMemory()) {
                            return;
                        }

                        $offset = ($page - 1) * static::RECORDS_PER_PAGE;
                        $result = $this->$method(array_slice($products, $offset, static::RECORDS_PER_PAGE));

                        if ($result !== true) {
                            $errors++;
                            $this->log(Zend_Log::ERR, sprintf("Errors occurred while attempting to %s products %d - %d: %s",
                                $action,
                                $offset + 1,
                                ($offset + static::RECORDS_PER_PAGE <= $total) ? $offset + static::RECORDS_PER_PAGE : $total,
                                $result
                            ));
                            /*$this->notify(
                                Mage::helper('klevu_search')->__("Product Sync for %s (%s) failed to %s some products. Please consult the logs for more details.",
                                    $store->getWebsite()->getName(),
                                    $store->getName(),
                                    $action
                                ),
                                $store
                            );*/
                        }
                    }
                }

                $this->log(Zend_Log::INFO, sprintf("Finished sync for %s (%s).", $store->getWebsite()->getName(), $store->getName()));
                
                /* Sync category content */
                $this->runCategory($store);
                
                if (!$config->isExtensionEnabled($store) && !$config->hasProductSyncRun($store)) {
                    // Enable Klevu Search after the first sync
                    if(!empty($firstSync)) {
                        $config->setExtensionEnabledFlag(true, $store);
                        $this->log(Zend_Log::INFO, sprintf("Automatically enabled Klevu Search on Frontend for %s (%s).",
                            $store->getWebsite()->getName(),
                            $store->getName()
                        ));
                    }
                    
                }
                $config->setLastProductSyncRun("now", $store);
			
                if ($errors == 0) {
                    // If Product Sync finished without any errors, notifications are not relevant anymore
                    $this->deleteNotifications($store);
                }
    
    }

    /**
     * Run the product sync manually, creating a cron schedule entry
     * to prevent other syncs from running.
     */
    public function runManually() {
        $time = date_create("now")->format("Y-m-d H:i:s");
		if(Mage::helper("klevu_search/config")->isExternalCronEnabled()) {
            $schedule = Mage::getModel("cron/schedule");
            $schedule
            ->setJobCode($this->getJobCode())
            ->setCreatedAt($time)
            ->setScheduledAt($time)
            ->setExecutedAt($time)
            ->setStatus(Mage_Cron_Model_Schedule::STATUS_RUNNING)
            ->save();
        }
        try {
            $this->run();
        } catch (Exception $e) {
            Mage::logException($e);
            
			if(Mage::helper("klevu_search/config")->isExternalCronEnabled()) {
                $schedule
                ->setMessages($e->getMessage())
                ->setStatus(Mage_Cron_Model_Schedule::STATUS_ERROR)
                ->save();
			}
            return;
        }

        $time = date_create("now")->format("Y-m-d H:i:s");
		if(Mage::helper("klevu_search/config")->isExternalCronEnabled()) {
            $schedule
            ->setFinishedAt($time)
            ->setStatus(Mage_Cron_Model_Schedule::STATUS_SUCCESS)
            ->save();
		}

        return;
    }

    /**
     * Mark all products to be updated the next time Product Sync runs.
     *
     * @param Mage_Core_Model_Store|int $store If passed, will only update products for the given store.
     *
     * @return $this
     */
    public function markAllProductsForUpdate($store = null) {
        $where = "";
        if ($store !== null) {
            $store = Mage::app()->getStore($store);

            $where = $this->getConnection()->quoteInto("store_id =  ?", $store->getId());
        }

        $this->getConnection()->update(
            $this->getTableName('klevu_search/product_sync'),
            array('last_synced_at' => '0'),
            $where
        );

        return $this;
    }

    /**
     * Forget the sync status of all the products for the given Store and test mode.
     * If no store or test mode status is given, clear products for all stores and modes respectively.
     *
     * @param Mage_Core_Model_Store|int|null $store
     * @param bool|null $test_mode
     *
     * @return int
     */
    public function clearAllProducts($store = null, $test_mode = null) {
        $select = $this->getConnection()
            ->select()
            ->from(
                array("k" => $this->getTableName("klevu_search/product_sync"))
            );

        if ($store) {
            $store = Mage::app()->getStore($store);

            $select->where("k.store_id = ?", $store->getId());
        }

        if ($test_mode !== null) {
            $test_mode = ($test_mode) ? 1 : 0;

            $select->where("k.test_mode = ?", $test_mode);
        }

        $result = $this->getConnection()->query($select->deleteFromSelect("k"));
        return $result->rowCount();
    }

    /**
     * Return the product status attribute model.
     *
     * @return Mage_Catalog_Model_Resource_Eav_Attribute
     */
    protected function getProductStatusAttribute() {
        if (!$this->hasData("status_attribute")) {
            $this->setData("status_attribute", Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'status'));
        }

        return $this->getData("status_attribute");
    }

    /**
     * Return the product visibility attribute model.
     *
     * @return Mage_Catalog_Model_Resource_Eav_Attribute
     */
    protected function getProductVisibilityAttribute() {
        if (!$this->hasData("visibility_attribute")) {
            $this->setData("visibility_attribute", Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'visibility'));
        }

        return $this->getData("visibility_attribute");
    }

    /**
     * Setup an API session for the given store. Sets the store and session ID on self. Returns
     * true on success or false if Product Sync is disabled, store is not configured or the
     * session API call fails.
     *
     * @param Mage_Core_Model_Store $store
     *
     * @return bool
     */
    protected function setupSession(Mage_Core_Model_Store $store) {
        $config = Mage::helper('klevu_search/config');

        if (!$config->isProductSyncEnabled($store->getId())) {
            $this->log(Zend_Log::INFO, sprintf("Disabled for %s (%s).", $store->getWebsite()->getName(), $store->getName()));
            return null;
        }

        $api_key = $config->getRestApiKey($store->getId());
        if (!$api_key) {
            $this->log(Zend_Log::INFO, sprintf("No API key found for %s (%s).", $store->getWebsite()->getName(), $store->getName()));
            return null;
        }

        $response = Mage::getModel('klevu_search/api_action_startsession')->execute(array(
            'api_key' => $api_key,
            'store' => $store,
        ));

        if ($response->isSuccessful()) {
            $this->addData(array(
                'store'      => $store,
                'session_id' => $response->getSessionId()
            ));
            return true;
        } else {
            $this->log(Zend_Log::ERR, sprintf("Failed to start a session for %s (%s): %s",
                $store->getWebsite()->getName(),
                $store->getName(),
                $response->getMessage()
            ));

            if ($response instanceof Klevu_Search_Model_Api_Response_Empty) {
                /*$this->notify(
                    Mage::helper('klevu_search')->__(
                        "Product Sync failed for %s (%s): Could not contact Klevu.",
                        $store->getWebsite()->getName(),
                        $store->getName()
                    )
                );*/
            } else {
				$this->notify(
					Mage::helper('klevu_search')->__(
						"Product Sync failed for %s (%s): %s",
						$store->getWebsite()->getName(),
						$store->getName(),
						$response->getMessage()
					),$store
				);
            }

            return false;
        }
    }

    /**
     * Delete the given products from Klevu Search. Returns true if the operation was
     * successful, or the error message if the operation failed.
     *
     * @param array $data List of products to delete. Each element should be an array
     *                    containing an element with "product_id" as the key and product id as
     *                    the value and an optional "parent_id" element with the parent id.
     *
     * @return bool|string
     */
    protected function deleteProducts(array $data) {
		Mage::getSingleton('core/session')->setKlevuFailedFlag(0);
        $total = count($data);
		$baseDomain = $this->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK,true);
		foreach($data as $key => $value){
			$data[$key]['url'] = $baseDomain; 
		}
        $response = Mage::getModel('klevu_search/api_action_deleterecords')
            ->setStore($this->getStore())
            ->execute(array(
            'sessionId' => $this->getSessionId(),
            'records'   => array_map(function ($v) {
                return array('id' => Mage::helper('klevu_search')->getKlevuProductId($v['product_id'], $v['parent_id']),'url' => $v['url']);
            }, $data)
        ));

        if ($response->isSuccessful()) {
			
            $connection = $this->getConnection();

            $select = $connection
                ->select()
                ->from(array('k' => $this->getTableName("klevu_search/product_sync")))
                ->where("k.store_id = ?", $this->getStore()->getId())
                ->where("k.type = ?","products")
                ->where("k.test_mode = ?", $this->isTestModeEnabled());

            $skipped_record_ids = array();
            if ($skipped_records = $response->getSkippedRecords()) {
                $skipped_record_ids = array_flip($skipped_records["index"]);
            }

            $or_where = array();
            for ($i = 0; $i < count($data); $i++) {
                if (isset($skipped_record_ids[$i])) {
                    continue;
                }
                $or_where[] = sprintf("(%s AND %s AND %s)",
                    $connection->quoteInto("k.product_id = ?", $data[$i]['product_id']),
                    $connection->quoteInto("k.parent_id = ?", $data[$i]['parent_id']),
                    $connection->quoteInto("k.type = ?", "products")
                );
            }
			
            $select->where(implode(" OR ", $or_where));
            $connection->query($select->deleteFromSelect("k"));

            $skipped_count = count($skipped_record_ids);
            if ($skipped_count > 0) {
                return sprintf("%d product%s failed (%s)",
                    $skipped_count,
                    ($skipped_count > 1) ? "s" : "",
                    implode(", ", $skipped_records["messages"])
                );
            } else {
                return true;
            }
        } else {
			Mage::getSingleton('core/session')->setKlevuFailedFlag(1);
            return sprintf("%d product%s failed (%s)",
                $total,
                ($total > 1) ? "s" : "",
                $response->getMessage()
            );
			
        }
    }

    /**
     * Update the given products on Klevu Search. Returns true if the operation was successful,
     * or the error message if it failed.
     *
     * @param array $data List of products to update. Each element should be an array
     *                    containing an element with "product_id" as the key and product id as
     *                    the value and an optional "parent_id" element with the parent id.
     *
     * @return bool|string
     */
    protected function updateProducts(array $data) {
		Mage::getSingleton('core/session')->setKlevuFailedFlag(0);
        $total = count($data);
        $dataToSend = $this->addProductSyncData($data);
		if(!empty($dataToSend) && is_numeric($dataToSend)){
		    $data = array_slice($data, 0, $dataToSend);
		}
        $response = Mage::getModel('klevu_search/api_action_updaterecords')
            ->setStore($this->getStore())
            ->execute(array(
            'sessionId' => $this->getSessionId(),
            'records'   => $data
        ));

        if ($response->isSuccessful()) {
			
            $helper = Mage::helper('klevu_search');
            $connection = $this->getConnection();

            $skipped_record_ids = array();
            if ($skipped_records = $response->getSkippedRecords()) {
                $skipped_record_ids = array_flip($skipped_records["index"]);
            }

            $where = array();
            for ($i = 0; $i < count($data); $i++) {
                if (isset($skipped_record_ids[$i])) {
                    continue;
                }
				if(isset($data[$i])) {
					$ids = $helper->getMagentoProductId($data[$i]['id']);

					$where[] = sprintf("(%s AND %s AND %s)",
						$connection->quoteInto("product_id = ?", $ids['product_id']),
						$connection->quoteInto("parent_id = ?", $ids['parent_id']),
						$connection->quoteInto("type = ?", "products")
					);
				}
            }
			
			if(!empty($where)) {
				$where = sprintf("(%s) AND (%s) AND (%s)",
					$connection->quoteInto("store_id = ?", $this->getStore()->getId()),
					$connection->quoteInto("test_mode = ?", $this->isTestModeEnabled()),
					implode(" OR ", $where)
				);

				$this->getConnection()->update(
					$this->getTableName('klevu_search/product_sync'),
					array('last_synced_at' => Mage::helper("klevu_search/compat")->now()),
					$where
				);
			}
            
            $skipped_count = count($skipped_record_ids);
            if ($skipped_count > 0) {
                return sprintf("%d product%s failed (%s)",
                    $skipped_count,
                    ($skipped_count > 1) ? "s" : "",
                    implode(", ", $skipped_records["messages"])
                );
            } else {
                return true;
            }
        } else {
			Mage::getSingleton('core/session')->setKlevuFailedFlag(1);
            return sprintf("%d product%s failed (%s)",
                $total,
                ($total > 1) ? "s" : "",
                $response->getMessage()
            );
			
        }
    }

    /**
     * Add the given products to Klevu Search. Returns true if the operation was successful,
     * or the error message if it failed.
     *
     * @param array $data List of products to add. Each element should be an array
     *                    containing an element with "product_id" as the key and product id as
     *                    the value and an optional "parent_id" element with the parent id.
     *
     * @return bool|string
     */
    protected function addProducts(array $data) {
		Mage::getSingleton('core/session')->setKlevuFailedFlag(0);
        $total = count($data);
        $dataToSend = $this->addProductSyncData($data);
		if(!empty($dataToSend) && is_numeric($dataToSend)){
		    $data = array_slice($data, 0, $dataToSend);
		}
        $response = Mage::getModel('klevu_search/api_action_addrecords')
            ->setStore($this->getStore())
            ->execute(array(
            'sessionId' => $this->getSessionId(),
            'records'   => $data
        ));

        if ($response->isSuccessful()) {
            $skipped_record_ids = array();
            if ($skipped_records = $response->getSkippedRecords()) {
                $skipped_record_ids = array_flip($skipped_records["index"]);
            }

            $sync_time = Mage::helper("klevu_search/compat")->now();

            foreach($data as $i => &$record) {
                if (isset($skipped_record_ids[$i])) {
                    unset($data[$i]);
                    continue;
                }

                $ids = Mage::helper("klevu_search")->getMagentoProductId($data[$i]['id']);

                $record = array(
                    $ids["product_id"],
                    $ids["parent_id"],
                    $this->getStore()->getId(),
                    $this->isTestModeEnabled(),
                    $sync_time,
                    "products"
                );
            }
			
			if(!empty($data)) {
				foreach($data as $key => $value){
					$write = $this->getConnection();
					$query = "replace into ".$this->getTableName('klevu_search/product_sync')
						   . "(product_id, parent_id, store_id, test_mode, last_synced_at, type) values "
						   . "(:product_id, :parent_id, :store_id, :test_mode, :last_synced_at, :type)";

					$binds = array(
						'product_id' => $value[0],
						'parent_id' => $value[1],
						'store_id' => $value[2],
						'test_mode' => $value[3],
						'last_synced_at'  => $value[4],
						'type' => $value[5]
					);
					$write->query($query, $binds);
				}
			}
			
			
		

            $skipped_count = count($skipped_record_ids);
            if ($skipped_count > 0) {
                return sprintf("%d product%s failed (%s)",
                    $skipped_count,
                    ($skipped_count > 1) ? "s" : "",
                    implode(", ", $skipped_records["messages"])
                );
            } else {
                return true;
            }
        } else {
			Mage::getSingleton('core/session')->setKlevuFailedFlag(1);
            return sprintf("%d product%s failed (%s)",
                $total,
                ($total > 1) ? "s" : "",
                $response->getMessage()
            );
        }
    }

    /**
     * Add the Product Sync data to each product in the given list. Updates the given
     * list directly to save memory.
     *
     * @param array $products An array of products. Each element should be an array with
     *                        containing an element with "id" as the key and the product
     *                        ID as the value.
     *
     * @return $this
     */
    protected function addProductSyncData(&$products) {
        $product_ids = array();
        $parent_ids = array();
        foreach ($products as $product) {
            $product_ids[] = $product['product_id'];
            if ($product['parent_id'] != 0) {
                $product_ids[] = $product['parent_id'];
                $parent_ids[] = $product['parent_id'];
            }
        }
        $product_ids = array_unique($product_ids);
        $parent_ids = array_unique($parent_ids);
        $data = Mage::getModel('catalog/product')->getCollection()
            ->addIdFilter($product_ids)
            ->setStore($this->getStore())
            ->addStoreFilter()
			->addFinalPrice()
            ->addAttributeToSelect($this->getUsedMagentoAttributes());

        $data->load()
            ->addCategoryIds();

        $url_rewrite_data = $this->getUrlRewriteData($product_ids);
        $visibility_data = $this->getVisibilityData($product_ids);
        //$configurable_price_data = $this->getConfigurablePriceData($parent_ids);

        $stock_data = $this->getStockData($product_ids);

        $attribute_map = $this->getAttributeMap();
        $config = Mage::helper('klevu_search/config');
        if($config->isSecureUrlEnabled($this->getStore()->getId())) {
            $base_url = $this->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK,true);
            $media_url = $this->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA,true);
          
       }else {
            $base_url = $this->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
            $media_url = $this->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        }
        $currency = $this->getStore()->getDefaultCurrencyCode();
        $media_url .= Mage::getModel('catalog/product_media_config')->getBaseMediaUrlAddition();
        Mage::app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_FRONTEND,Mage_Core_Model_App_Area::PART_EVENTS);
		$backend = Mage::getResourceModel('catalog/product_attribute_backend_media');
		$attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product', 'media_gallery');
		$container = new Varien_Object(array(
			'attribute' => new Varien_Object(array('id' => $attributeId))
		));
		$rc = 0;
        foreach ($products as $index => &$product) {
			
			if($rc % 5 == 0) {
				if ($this->rescheduleIfOutOfMemory()) {
                    return $rc;
				}
			}
			
			if($config->getCollectionMethod()) {
				$item = $data->getItemById($product['product_id']);
				$parent = ($product['parent_id'] != 0) ?  $data->getItemById($product['parent_id']) : null;
				$this->log(Zend_Log::DEBUG, sprintf("Retrieve data for product ID %d using collection method", $product['product_id']));
				$this->log(Zend_Log::DEBUG, sprintf("Retrieve data for product ID Parent ID %d using collection method", $product['parent_id']));
			} else {
				$item = Mage::getModel('catalog/product')->load($product['product_id']);
				$item->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
				$this->log(Zend_Log::DEBUG, sprintf("Retrieve data for product ID %d", $product['product_id']));
				$parent = ($product['parent_id'] != 0) ? Mage::getModel('catalog/product')->load($product['parent_id'])->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID): null;
				$this->log(Zend_Log::DEBUG, sprintf("Retrieve data for product ID Parent ID %d", $product['parent_id']));
			}
			
            if (!$item) {
                // Product data query did not return any data for this product
                // Remove it from the list to skip syncing it
                $this->log(Zend_Log::WARN, sprintf("Failed to retrieve data for product ID %d", $product['product_id']));
                unset($products[$index]);
                continue;
            }
            
            /* Use event to add any external module data to product */
            Mage::dispatchEvent('add_external_data_to_sync', array(
                'parent' => $parent,
                'product'=> &$product,
                'store' => $this->getStore()
            ));

            // Add data from mapped attributes
            foreach ($attribute_map as $key => $attributes) {
                $product[$key] = null;

                switch ($key) {
                    case "boostingAttribute":
                        foreach ($attributes as $attribute) {
                            if ($parent && $parent->getData($attribute)) {
                                $product[$key] = $parent->getData($attribute);
                                break;
                            } else {
                                $product[$key] = $item->getData($attribute);
                                break;
                            }
                        }
                        break;
                    case "rating":
                        foreach ($attributes as $attribute) {
                            if ($parent && $parent->getData($attribute)) {
                                $product[$key] = $this->convertToRatingStar($parent->getData($attribute));
                                break;
                            } else {
                                $product[$key] = $this->convertToRatingStar($item->getData($attribute));
                                break;
                            }
                        }
                        break;                        
                    case "otherAttributeToIndex":
                    case "other":
                        $product[$key] = array();
                        foreach ($attributes as $attribute) {
                            if ($item->getData($attribute)) {
                                $product[$key][$attribute] = $this->getAttributeData($attribute, $item->getData($attribute));
                            } else if ($parent && $parent->getData($attribute)) {
                                $product[$key][$attribute] = $this->getAttributeData($attribute, $parent->getData($attribute));
                            }
                        }
                        break;
                     case "sku":
                        foreach ($attributes as $attribute) {
                            if ($parent && $parent->getData($attribute)) {
                                $product[$key] = Mage::helper('klevu_search')->getKlevuProductSku($item->getData($attribute), $parent->getData($attribute));
                                break;
                            } else {
                                $product[$key] = $item->getData($attribute);
                                break;
                            }
                        }
                        break;
                    case "name":
                        foreach ($attributes as $attribute) {
                            if ($parent && $parent->getData($attribute)) {
                                $product[$key] = $parent->getData($attribute);
                                break;
                            }else if ($item->getData($attribute)) {
                                $product[$key] = $item->getData($attribute);
                                break;
                            }
                        }
                        break;
					case "desc":
                        foreach ($attributes as $attribute) {
								if ($parent && $parent->getData($attribute)) {
									$product[$key] = $parent->getData($attribute).$item->getData($attribute);
									break;
								} else {
									$product[$key] = $item->getData($attribute);
									break;
								}
                        }
                        break;
					case "shortDesc":
                        foreach ($attributes as $attribute) {
							if($config->isUseConfigDescription($this->getStore()->getId())) {
								if ($parent && $parent->getData($attribute)) {
									$product[$key] = $parent->getData($attribute);
									break;
								} else {
									$product[$key] = $item->getData($attribute);
									break;
								}
							} else {
								if ($item->getData($attribute)) {
									$product[$key] = $item->getData($attribute);
									break;
								} else {
                                    if ($parent && $parent->getData($attribute)) {
                                        $product[$key] = $parent->getData($attribute);
                                        break;
                                    }
								}
							}
                        }
                        break;
                    case "image":
					    $config = Mage::helper('klevu_search/config');
                        foreach ($attributes as $attribute) {
							if($config->isUseConfigImage($this->getStore()->getId())) {
							    if ($parent && $parent->getData($attribute) && $parent->getData($attribute) != "no_selection") {
									$product[$key] = $parent->getData($attribute);
									break;
								} else if ($item->getData($attribute) && $item->getData($attribute) != "no_selection") {
									$product[$key] = $item->getData($attribute);
									break;
								}
								
								if ($parent && $parent->getData($attribute) == "no_selection") {
									$product[$key] = $parent->getData('small_image');
									if($product[$key] == "no_selection"){
										$product_media = new Varien_Object(array(
											'id' => $product['parent_id'],
											'store_id' => $this->getStore()->getId(),
										));
										$media_image = $backend->loadGallery($product_media, $container);
										if(count($media_image) > 0) {
									        $product[$key] = $media_image[0]['file'];
										}
									}
									break;
								} else if ($item->getData($attribute) && $item->getData($attribute) == "no_selection") {
									$product[$key] = $item->getData('small_image');
									if($product[$key] == "no_selection"){
									    $product_media = new Varien_Object(array(
											'id' => $product['product_id'],
											'store_id' => $this->getStore()->getId(),
										));
										$media_image = $backend->loadGallery($product_media, $container);
										if(count($media_image) > 0) {
									        $product[$key] = $media_image[0]['file'];
										}
									}
									break;
								}
								
							} else {
								if ($item->getData($attribute) && $item->getData($attribute) != "no_selection") {
									$product[$key] = $item->getData($attribute);
									break;
								} else if ($parent && $parent->getData($attribute) && $parent->getData($attribute) != "no_selection") {
									$product[$key] = $parent->getData($attribute);
									break;
								}

								if ($item->getData($attribute) && $item->getData($attribute) == "no_selection") {
									$product[$key] = $item->getData('small_image');
									if($product[$key] == "no_selection"){
										$product_media = new Varien_Object(array(
											'id' => $product['product_id'],
											'store_id' => $this->getStore()->getId(),
										));
										$media_image = $backend->loadGallery($product_media, $container);
										
										if(count($media_image) > 0) {
									        $product[$key] = $media_image[0]['file'];
										}
									}
									break;
								} else if ($parent && $parent->getData($attribute) && $parent->getData($attribute) == "no_selection") {
									$product[$key] = $parent->getData('small_image');
									if($product[$key] == "no_selection"){
										$product_media = new Varien_Object(array(
											'id' => $product['parent_id'],
											'store_id' => $this->getStore()->getId(),
										));
										$media_image = $backend->loadGallery($product_media, $container);
										if(count($media_image) > 0) {
									        $product[$key] = $media_image[0]['file'];
										}
									}
									break;
								}
								
							}
                        }
						if(!is_array($product[$key])) {
							if ($product[$key] != "" && strpos($product[$key], "http") !== 0) {
								if(strpos($product[$key],"/", 0) !== 0 && !empty($product[$key]) && $product[$key]!= "no_selection" ){
									$product[$key] = "/".$product[$key];
								}
								// Prepend media base url for relative image locations
								//generate thumbnail image for each products
								Mage::getModel('klevu_search/product_sync')->thumbImage($product[$key]);
								$imageResized = Mage::getBaseDir('media').DS."klevu_images".$product[$key];
									if (file_exists($imageResized)) {
										$config = Mage::helper('klevu_search/config');
										if($config->isSecureUrlEnabled($this->getStore()->getId())) {
											$product[$key] =  $this->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA,true)."klevu_images".$product[$key];
										} else {
											$product[$key] =  $this->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)."klevu_images".$product[$key];
										}
									}else{
										if(empty($product[$key]) || $product[$key] == "no_selection") {
											$placeholder_image = Mage::getStoreConfig("catalog/placeholder/small_image_placeholder");
											if(!empty($placeholder_image)) {
												$product[$key] = $media_url .'/placeholder/' .Mage::getStoreConfig("catalog/placeholder/small_image_placeholder");
											} else {
												$product[$key] = $media_url .'/placeholder/' .Mage::getStoreConfig("catalog/placeholder/image_placeholder");	
											}
										}else {
											 $product[$key] = $media_url . $product[$key];
										}
									}
							}
						} else {
							$placeholder_image = Mage::getStoreConfig("catalog/placeholder/small_image_placeholder");
							if(!empty($placeholder_image)) {
								$product[$key] = $media_url .'/placeholder/' .Mage::getStoreConfig("catalog/placeholder/small_image_placeholder");
							} else {
								$product[$key] = $media_url .'/placeholder/' .Mage::getStoreConfig("catalog/placeholder/image_placeholder");	
							}
						}
                        break;
                    case "salePrice":
                        // Default to 0 if price can't be determined
                        $product['salePrice'] = 0;
                        $tax_class_id = "";
                        if ($item->getData("tax_class_id") !== null) {
                            $tax_class_id = $item->getData("tax_class_id");
                        } else if ($parent) {
                            $tax_class_id = $parent->getData("tax_class_id");
                        }else {
                            $tax_class_id = "";
                        }
			
                        if ($parent && $parent->getData("type_id") == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
                            // Calculate configurable product price based on option values
                            $fprice = $parent->getFinalPrice();
                            $price = (isset($fprice)) ? $fprice: $parent->getData("price");

                            // show low price for config products
                            $product['startPrice'] = $this->processPrice($price , $tax_class_id, $parent);
                            
                            // also send sale price for sorting and filters for klevu 
                            $product['salePrice'] = $this->processPrice($price , $tax_class_id, $parent);
                        } else {
                            // Use price index prices to set the product price and start/end prices if available
                            // Falling back to product price attribute if not
                            if ($item) {
                                
                                // Always use minimum price as the sale price as it's the most accurate
                                $product['salePrice'] = $this->processPrice($item->getFinalPrice(), $tax_class_id, $item);
                                
                                if ($item->getData('type_id') == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
                                    Mage::helper('klevu_search')->getGroupProductMinPrice($item,$this->getStore());
                                    $sPrice = $item->getFinalPrice();
                                    $product['startPrice'] = $this->processPrice($sPrice, $tax_class_id, $item);
                                    $product["salePrice"] = $this->processPrice($sPrice, $tax_class_id, $item);
                                }
                                
                                if ($item->getData('type_id') == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                                    list($minimalPrice, $maximalPrice) = Mage::helper('klevu_search')->getBundleProductPrices($item,$this->getStore());
                                    $product["salePrice"] = $this->processPrice($minimalPrice, $tax_class_id, $item);
                                    $product['startPrice'] = $this->processPrice($minimalPrice, $tax_class_id, $item);
                                    $product['toPrice'] = $this->processPrice($maximalPrice, $tax_class_id, $item);
                                }
                                
                            } else {
                                if ($item->getData("price") !== null) {
                                    $product["salePrice"] = $this->processPrice($item->getData("price"), $tax_class_id, $item);
                                } else if ($parent) {
                                    $product["salePrice"] = $this->processPrice($parent->getData("price"), $tax_class_id, $parent);
                                }
                            }
                        }
						
                        break;
                    case "price":
                            // Default to 0 if price can't be determined
                            $product['price'] = 0;
                            $tax_class_id = "";
                            if ($parent && $parent->getData("type_id") == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
                              // Calculate configurable product price based on option values
                              $orgPrice = $parent->getPrice();
                              $price = (isset($orgPrice)) ? $orgPrice: $parent->getData("price");

                              // also send sale price for sorting and filters for klevu 
                              $product['price'] = $this->processPrice($price , $tax_class_id, $parent);
                            } else {
                              // Use price index prices to set the product price and start/end prices if available
                              // Falling back to product price attribute if not
                                if ($item) {
                                  
                                  // Always use minimum price as the sale price as it's the most accurate
                                  $product['price'] = $this->processPrice($item->getPrice(), $tax_class_id, $item);
                                  
                                    if ($item->getData('type_id') == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
                                        // Get the group product original price 
                                        Mage::helper('klevu_search')->getGroupProductOriginalPrice($item,$this->getStore());
                                        $sPrice = $item->getPrice();
                                        $product["price"] = $this->processPrice($sPrice, $tax_class_id, $item);
                                    }
                                  
                                    if ($item->getData('type_id') == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                                        
                                        // product detail page always shows final price as price so we also taken final price as original price only for bundle product 
                                        list($minimalPrice, $maximalPrice) = Mage::helper('klevu_search')->getBundleProductPrices($item,$this->getStore());
                                        $product["price"] = $this->processPrice($minimalPrice, $tax_class_id, $item);
                                    }
                                  
                                } else {
                                    if ($item->getData("price") !== null) {
                                        $product["price"] = $this->processPrice($item->getData("price"), $tax_class_id, $item);
                                    } else if ($parent) {
                                        $product["price"] = $this->processPrice($parent->getData("price"), $tax_class_id, $parent);
                                    }
                                }
                            }
                        break;
                    default:
                        foreach ($attributes as $attribute) {
                            if ($item->getData($attribute)) {
                                $product[$key] = $this->getAttributeData($attribute, $item->getData($attribute));
                                break;
                            } else if ($parent && $parent->getData($attribute)) {
                                $product[$key] = $this->getAttributeData($attribute, $parent->getData($attribute));
                                break;
                            }
                        }
                }
            }

            // Add non-attribute data
            $product['currency'] = $currency;

            if ($parent) {
                $product['category'] = $this->getLongestPathCategoryName($parent->getCategoryIds());
                $product['listCategory'] = $this->getCategoryNames($parent->getCategoryIds());
			} else if ($item->getCategoryIds()) {
                $product['category'] = $this->getLongestPathCategoryName($item->getCategoryIds());
                $product['listCategory'] = $this->getCategoryNames($item->getCategoryIds());
            } else {
                $product['category'] = "";
                $product['listCategory'] = "KLEVU_PRODUCT";
            }
            
            
            if ($parent) {
                //Get the price based on customer group
                $product['groupPrices'] = $this->getGroupPrices($parent);
            } else if($item) {
                $product['groupPrices'] = $this->getGroupPrices($item);
            } else {
                $product['groupPrices'] = "";
            }
            
            

            // Use the parent URL if the product is invisible (and has a parent) and
            // use a URL rewrite if one exists, falling back to catalog/product/view
            if (isset($visibility_data[$product['product_id']]) && !$visibility_data[$product['product_id']] && $parent) {
                $product['url'] = $base_url . (
                    (isset($url_rewrite_data[$product['parent_id']])) ?
                        $url_rewrite_data[$product['parent_id']] :
                        "catalog/product/view/id/" . $product['parent_id']
                    );
            } else {
                if($parent) {
                  $product['url'] = $base_url . (
                      (isset($url_rewrite_data[$product['parent_id']])) ?
                          $url_rewrite_data[$product['parent_id']] :
                          "catalog/product/view/id/" . $product['parent_id']
                      );                
                } else {
                  $product['url'] = $base_url . (
                    (isset($url_rewrite_data[$product['product_id']])) ?
                        $url_rewrite_data[$product['product_id']] :
                        "catalog/product/view/id/" . $product['product_id']
                    );
                }
            }

            // Add stock data
			$product['inStock'] = ($stock_data[$product['product_id']]) ? "yes" : "no";
	

            // Configurable product relation
            if ($product['parent_id'] != 0) {
                $product['itemGroupId'] = $product['parent_id'];
            }

            // Set ID data
            $product['id'] = Mage::helper('klevu_search')->getKlevuProductId($product['product_id'], $product['parent_id']);
			
			
			if($item) {
			    $item->clearInstance();
				$item = null;
			}
			
			if($parent) {
				if(!$config->getCollectionMethod()) {
					$parent->clearInstance();
					$parent = null;
				}
			}
            unset($product['product_id']);
            unset($product['parent_id']);
			$rc++;
        }

        return $this;
    }

    /**
     * Return the URL rewrite data for the given products for the current store.
     *
     * @param array $product_ids A list of product IDs.
     *
     * @return array A list with product IDs as keys and request paths as values.
     */
    protected function getUrlRewriteData($product_ids) {
        $stmt = $this->getConnection()->query(
            Mage::helper('klevu_search/compat')->getProductUrlRewriteSelect($product_ids, 0, $this->getStore()->getId())
        );

        $url_suffix = Mage::helper('catalog/product')->getProductUrlSuffix($this->getStore()->getId());
        if ($url_suffix && substr($url_suffix, 0, 1) !== ".") {
            $url_suffix = "." . $url_suffix;
        }

        $data = array();
        while ($row = $stmt->fetch()) {
            if (!isset($data[$row['product_id']])) {
                $data[$row['product_id']] = $row['request_path'];
                // Append the product URL suffix if the rewrite does not have one already
                if ($url_suffix && substr($row['request_path'], -1 * strlen($url_suffix)) !== $url_suffix) {
                    $data[$row['product_id']] .= $url_suffix;
                }
            }
        }

        return $data;
    }

    /**
     * Return the visibility data for the given products for the current store.
     *
     * @param array $product_ids A list of product IDs.
     *
     * @return array A list with product IDs as keys and boolean visibility values.
     */
    protected function getVisibilityData($product_ids) {
        $stmt = $this->getConnection()->query(
            $this->getConnection()
                ->select()
                ->from(
                    array('p' => $this->getTableName("catalog/product")),
                    array(
                        'product_id' => "p.entity_id"
                    )
                )
                ->joinLeft(
                    array('vs' => $this->getProductVisibilityAttribute()->getBackendTable()),
                    "vs.attribute_id = :visibility_attribute_id AND vs.entity_id = p.entity_id AND vs.store_id = :store_id",
                    ""
                )
                ->joinLeft(
                    array('vd' => $this->getProductVisibilityAttribute()->getBackendTable()),
                    "vd.attribute_id = :visibility_attribute_id AND vd.entity_id = p.entity_id AND vd.store_id = :default_store_id",
                    array(
                        "visibility" => new Zend_Db_Expr("IF(vs.value IS NOT NULL, vs.value, vd.value)")
                    )
                )
                ->where("p.entity_id IN (?)", $product_ids),
            array(
                "visibility_attribute_id" => $this->getProductVisibilityAttribute()->getId(),
                "store_id"                => $this->getStore()->getId(),
                "default_store_id"        => Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID
            )
        );

        $data = array();
        while ($row = $stmt->fetch()) {
            $data[$row['product_id']] = ($row['visibility'] != Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) ? true : false;
        }

        return $data;
    }

    /**
     * Return the "Is in stock?" flags for the given products.
     * Considers if the stock is managed on the product or per store when deciding if a product
     * is in stock.
     *
     * @param array $product_ids A list of product IDs.
     *
     * @return array A list with product IDs as keys and "Is in stock?" booleans as values.
     */
    protected function getStockData($product_ids) {
        $stmt = $this->getConnection()->query(
            $this->getConnection()
                ->select()
                ->from(
                    array('s' => $this->getTableName("cataloginventory/stock_item")),
                    array(
                        'product_id'   => "s.product_id",
                        'in_stock'     => "s.is_in_stock",
                        'manage_stock' => "s.manage_stock",
                        'use_config'   => "s.use_config_manage_stock",
                    )
                )
                ->where("s.product_id IN (?)", $product_ids)
				->where("s.stock_id = ?",1)
        );

        $data = array();
        while ($row = $stmt->fetch()) {
            if (($row['use_config'] && $this->getStoreManageStock()) || (!$row['use_config'] && $row['manage_stock'])) {
                $data[$row['product_id']] = ($row['in_stock']) ? true : false;
            } else {
                $data[$row['product_id']] = true;
            }
        }

        return $data;
    }

    /**
     * Return the configurable price information (price markup for each value of each configurable
     * attribute) for the given configurable product IDs.
     *
     * @param $parent_ids
     *
     * @return array
     */
    protected function getConfigurablePriceData($parent_ids) {
        $default_website_id = Mage::app()->getStore(Mage_Core_Model_Store::ADMIN_CODE)->getWebsiteId();
        $store_website_id = $this->getStore()->getWebsiteId();
        $sort_order = ($default_website_id > $store_website_id) ? Varien_Db_Select::SQL_ASC : Varien_Db_Select::SQL_DESC;

        $stmt = $this->getConnection()->query(
            $this->getConnection()
                ->select()
                ->from(array("s" => $this->getTableName("catalog/product_super_attribute")), "")
                ->join(array("a" => $this->getTableName("eav/attribute")), "s.attribute_id = a.attribute_id", "")
                ->join(array("p" => $this->getTableName("catalog/product_super_attribute_pricing")), "s.product_super_attribute_id = p.product_super_attribute_id", "")
                ->columns(array(
                    "parent_id" => "s.product_id",
                    "attribute_code" => "a.attribute_code",
                    "attribute_value" => "p.value_index",
                    "price_is_percent" => "p.is_percent",
                    "price_value" => "p.pricing_value"
                ))
                ->where("s.product_id IN (?)", $parent_ids)
                ->where("p.website_id IN (?)", array($default_website_id, $store_website_id))
                ->order(array(
                    "s.product_id " . Varien_Db_Select::SQL_ASC,
                    "a.attribute_code " . Varien_Db_Select::SQL_ASC,
                    "p.website_id " . $sort_order
                ))
                ->group(array("s.product_id", "a.attribute_code", "p.value_index"))
        );

        $data = array();
        while ($row = $stmt->fetch()) {
            if (!isset($data[$row["parent_id"]])) {
                $data[$row["parent_id"]] = array();
            }
            if (!isset($data[$row["parent_id"]][$row["attribute_code"]])) {
                $data[$row["parent_id"]][$row["attribute_code"]] = array();
            }
            if (!isset($data[$row["parent_id"]][$row["attribute_code"]][$row["attribute_value"]])) {
                $data[$row["parent_id"]][$row["attribute_code"]][$row["attribute_value"]] = array(
                    "is_percent" => ($row["price_is_percent"]) ? true : false,
                    "value"      => $row["price_value"]
                );
            }
        }

        return $data;
    }

    /**
     * Return a map of Klevu attributes to Magento attributes.
     *
     * @return array
     */
    protected function getAttributeMap() {
        if (!$this->hasData('attribute_map')) {
            $attribute_map = array();

            $automatic_attributes = $this->getAutomaticAttributes();
            $attribute_map = $this->prepareAttributeMap($attribute_map, $automatic_attributes);

            $additional_attributes = Mage::helper('klevu_search/config')->getAdditionalAttributesMap($this->getStore());
            $attribute_map = $this->prepareAttributeMap($attribute_map, $additional_attributes);

			$default_attribute_to_index =  array("news_from_date","news_to_date","created_at");
            // Add otherAttributeToIndex to $attribute_map.
            $otherAttributeToIndex = array_merge($default_attribute_to_index,Mage::helper('klevu_search/config')->getOtherAttributesToIndex($this->getStore()));
			
			
            if(!empty($otherAttributeToIndex)) {
                $attribute_map['otherAttributeToIndex'] = $otherAttributeToIndex;
            }
            
            // Add boostingAttribute to $attribute_map.
            $boosting_value = Mage::helper('klevu_search/config')->getBoostingAttribute($this->getStore());
            if($boosting_value != "use_boosting_rule") {
                if(($boosting_attribute = Mage::helper('klevu_search/config')->getBoostingAttribute($this->getStore())) && !is_null($boosting_attribute)) {
                    $attribute_map['boostingAttribute'][] = $boosting_attribute;
                }
            }
            $this->setData('attribute_map', $attribute_map);
        }

        return $this->getData('attribute_map');
    }

    /**
     * Returns an array of all automatically matched attributes. Includes defaults and filterable in search attributes.
     * @return array
     */
    public function getAutomaticAttributes() {
        if(!$this->hasData('automatic_attributes')) {
            // Default mapped attributes
            $default_attributes = Mage::helper('klevu_search/config')->getDefaultMappedAttributes();
            $attributes = array();
            for($i = 0; $i < count($default_attributes['klevu_attribute']); $i++) {
                $attributes[] = array(
                    'klevu_attribute' => $default_attributes['klevu_attribute'][$i],
                    'magento_attribute' => $default_attributes['magento_attribute'][$i]
                );
            }

            // Get all layered navigation / filterable in search attributes
            foreach($this->getLayeredNavigationAttributes() as $layeredAttribute) {
                $attributes[] = array (
                    'klevu_attribute' => 'other',
                    'magento_attribute' => $layeredAttribute
                );
            }

            $this->setData('automatic_attributes', $attributes);
            // Update the store system config with the updated automatic attributes map.
            Mage::helper('klevu_search/config')->setAutomaticAttributesMap($attributes, $this->getStore());
        }

        return $this->getData('automatic_attributes');
    }

    /**
     * Takes system configuration attribute data and adds to $attribute_map
     * @param $attribute_map
     * @param $additional_attributes
     * @return array
     */
    protected function prepareAttributeMap($attribute_map, $additional_attributes) {

        foreach ($additional_attributes as $mapping) {
            if (!isset($attribute_map[$mapping['klevu_attribute']])) {
                $attribute_map[$mapping['klevu_attribute']] = array();
            }
            $attribute_map[$mapping['klevu_attribute']][] = $mapping['magento_attribute'];
        }
        return $attribute_map;
    }

    /**
     * Return the attribute codes for all filterable in search attributes.
     * @return array
     */
    protected function getLayeredNavigationAttributes() {
        $attributes = Mage::helper('klevu_search/config')->getDefaultMappedAttributes();
        $select = $this->getConnection()
            ->select()
            ->from(
                array("a" => $this->getTableName("eav/attribute")),
                array("attribute" => "a.attribute_code")
            )
            ->join(
                array("ca" => $this->getTableName("catalog/eav_attribute")),
                "ca.attribute_id = a.attribute_id",
                ""
            )
            // Only if the attribute is filterable in search, i.e. attribute appears in search layered navigation.
            ->where("ca.is_filterable_in_search = ?", "1")
            // Make sure we exclude the attributes thar synced by default.
            ->where("a.attribute_code NOT IN(?)", array_unique($attributes['magento_attribute']))
            ->group(array("attribute_code"));

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Return the attribute codes for all attributes currently used in
     * configurable products.
     *
     * @return array
     */
    protected function getConfigurableAttributes() {
        $select = $this->getConnection()
            ->select()
            ->from(
                array("a" => $this->getTableName("eav/attribute")),
                array("attribute" => "a.attribute_code")
            )
            ->join(
                array("s" => $this->getTableName("catalog/product_super_attribute")),
                "a.attribute_id = s.attribute_id",
                ""
            )
            ->group(array("a.attribute_code"));

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Return a list of all Magento attributes that are used by Product Sync
     * when collecting product data.
     *
     * @return array
     */
    protected function getUsedMagentoAttributes() {
        $result = array();

        foreach ($this->getAttributeMap() as $attributes) {
            $result = array_merge($result, $attributes);
        }

        $result = array_merge($result, $this->getConfigurableAttributes());

        return array_unique($result);
    }

    /**
     * Return an array of category paths for all the categories in the
     * current store, not including the store root.
     *
     * @return array A list of category paths where each key is a category
     *               ID and each value is an array of category names for
     *               each category in the path, the last element being the
     *               name of the category referenced by the ID.
     */
	protected function getCategoryPaths() {
        if (!$category_paths = $this->getData('category_paths')) {
            $category_paths = array();
            $rootId = $this->getStore()->getRootCategoryId();  
            $collection = Mage::getResourceModel('catalog/category_collection')
                ->setStoreId($this->getStore()->getId())
				->addAttributeToSelect('exclude_in_search')
                ->addFieldToFilter('level', array('gt' => 1))
                ->addFieldToFilter('path', array('like'=> "1/$rootId/%"))
                ->addIsActiveFilter()
                ->addNameToResult();

            foreach ($collection as $category) {
                    $category_paths[$category->getId()] = array();
                    $path_ids = $category->getPathIds();
                    foreach ($path_ids as $id) {
                        if ($item = $collection->getItemById($id)) {
							if($category->getExcludeInSearch() != 1) {
								$category_paths[$category->getId()][] = $item->getName();
				
							}
                        }
                    }
				
            }
            $this->setData('category_paths', $category_paths);
        }
        return $category_paths;
    }

    /**
     * Return a list of the names of all the categories in the
     * paths of the given categories (including the given categories)
     * up to, but not including the store root.
     *
     * @param array $categories
     *
     * @return array
     */
    protected function getCategoryNames(array $categories) {
        $category_paths = $this->getCategoryPaths();

        $result = array("KLEVU_PRODUCT");
        foreach ($categories as $category) {
            if (isset($category_paths[$category])) {
                $result = array_merge($result, $category_paths[$category]);
            }
        }

        return array_unique($result);
    }

    /**
     * Given a list of category IDs, return the name of the category
     * in that list that has the longest path.
     *
     * @param array $categories
     *
     * @return string
     */
    protected function getLongestPathCategoryName(array $categories) {
        $category_paths = $this->getCategoryPaths();

        $length = 0;
        $name = "";
        foreach ($categories as $id) {
            if (isset($category_paths[$id])) {
                //if (count($category_paths[$id]) > $length) {
                    //$length = count($category_paths[$id]);
                    $name .= end($category_paths[$id]).";";
                //}
            }
        }
        return substr($name,0,strrpos($name,";")+1-1);
    }
    
    /**
     * Get the list of prices based on customer group
     *
     * @param object $item OR $parent
     *
     * @return array
     */
    protected function getGroupPrices($proData) {
        try {
             $groupPrices = $proData->getData('group_price');
            if (is_null($groupPrices)) {
                $attribute = $proData->getResource()->getAttribute('group_price');
                if ($attribute){
                    $attribute->getBackend()->afterLoad($proData);
                    $groupPrices = $proData->getData('group_price');
                }
            }

            if (!empty($groupPrices) && is_array($groupPrices)) {
                foreach ($groupPrices as $groupPrice) {
                    if($this->getStore()->getWebsiteId()== $groupPrice['website_id'] || $groupPrice['website_id']==0) {  
                        $groupPriceKey = $groupPrice['cust_group'];
                        $groupname = Mage::getModel('customer/group')->load($groupPrice['cust_group'])->getCustomerGroupCode();
                        $result['label'] =  $groupname;
                        $result['values'] =  $groupPrice['website_price'];
                        $priceGroupData[$groupPriceKey]= $result;
                    }
                }
                return $priceGroupData;
            }
        } catch(Exception $e) {
            Mage::helper('klevu_search')->log(Zend_Log::DEBUG, sprintf("Unable to get group price data for product id %s",$proData->getId()));
        }
    }

    /**
     * Returns either array containing the label and value(s) of an attribute, or just the given value
     *
     * In the case that there are multiple options selected, all values are returned
     *
     * @param string $code
     * @param null   $value
     *
     * @return array|string
     */
    protected function getAttributeData($code, $value = null) {
        if (!$attribute_data = $this->getData('attribute_data')) {
            $attribute_data = array();

            $collection = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addFieldToFilter('attribute_code', array('in' => $this->getUsedMagentoAttributes()));

            foreach ($collection as $attr) {
                $attr->setStoreId($this->getStore()->getId());
                $attribute_data[$attr->getAttributeCode()] = array(
                    'label' => $attr->getStoreLabel($this->getStore()->getId()),
                    'values' => ''
                );

                if ($attr->usesSource()) {
//                    $attribute_data[$attr->getAttributeCode()] = array();
                    foreach($attr->setStoreId($this->getStore()->getId())->getSource()->getAllOptions(false) as $option) {
                        if (is_array($option['value'])) {
                            foreach ($option['value'] as $sub_option) {
                                if(count($sub_option) > 0) {
                                    $attribute_data[$attr->getAttributeCode()]['values'][$sub_option['value']] = $sub_option['label'];
                                }
                            }
                        } else {
                            $attribute_data[$attr->getAttributeCode()]['values'][$option['value']] = $option['label'];
                        }
                    }
                }
            }

            $this->setData('attribute_data', $attribute_data);
        }
        // make sure the attribute exists
        if (isset($attribute_data[$code])) {
            // was $value passed a parameter?
            if (!is_null($value)) {
                // If not values are set on attribute_data for the attribute, return just the value passed. (attributes like: name, description etc)
                if(empty($attribute_data[$code]['values'])) {
                    return $value;
                }
                // break up our value into an array by a comma, this is for catching multiple select attributes.
                $values = explode(",", $value);

                // loop over our array of attribute values
                foreach ($values as $key => $valueOption) {
                    // if there is a value on the attribute_data use that value (it will be the label for a dropdown select attribute)
                    if (isset($attribute_data[$code]['values'][$valueOption])) {
                        $values[$key] = $attribute_data[$code]['values'][$valueOption];
                    } else { // If no label was found, log an error and unset the value.
                        Mage::helper('klevu_search')->log(Zend_Log::WARN, sprintf("Attribute: %s option label was not found, option ID provided: %s", $code, $valueOption));
                        unset($values[$key]);
                    }
                }

                // If there was only one value in the array, return the first (select menu, single option), or if there was more, return them all (multi-select).
                if (count($values) == 1) {
                    $attribute_data[$code]['values'] = $values[0];
                } else {
                    $attribute_data[$code]['values'] =  $values;
                }

            }
            return $attribute_data[$code];
        }

        $result['label'] = $code;
        $result['values'] = $value;
        return $result;
    }

    /**
     * Apply tax to the given price, if needed, remove if not.
     *
     * @param float $price
     * @param int $tax_class_id The tax class to use.
     *
     * @return float
     */
    protected function applyTax($price, $tax_class_id) {
        if ($this->usePriceInclTax()) {
            if (!$this->priceIncludesTax()) {
                // We need to include tax in the price
                $price += $this->calcTaxAmount($price, $tax_class_id, false);
            }
        } else {
            if ($this->priceIncludesTax()) {
                // Price includes tax, but we don't need it
                $price -= $this->calcTaxAmount($price, $tax_class_id, true);
            }
        }

        return $price;
    }

    /**
     * Calculate the amount of tax on the given price.
     *
     * @param      $price
     * @param      $tax_class_id
     * @param bool $price_includes_tax
     *
     * @return float
     */
    protected function calcTaxAmount($price, $tax_class_id, $price_includes_tax = false) {
        $calc = Mage::getSingleton("tax/calculation");

        if (!$tax_rates = $this->getData("tax_rates")) {
            // Get tax rates for the default destination
            $tax_rates = $calc->getRatesForAllProductTaxClasses($calc->getRateOriginRequest($this->getStore()));
            $this->setData("tax_rates", $tax_rates);
        }

        if (isset($tax_rates[$tax_class_id])) {
            return $calc->calcTaxAmount($price, $tax_rates[$tax_class_id], $price_includes_tax);
        }

        return 0.0;
    }

    /**
     * Convert the given price into the current store currency.
     *
     * @param $price
     *
     * @return float
     */
    protected function convertPrice($price) {
        return $this->getStore()->convertPrice($price, false);
    }

    /**
     * Process the given product price for using in Product Sync.
     * Applies tax, if needed, and converts to the currency of the current store.
     *
     * @param $price
     * @param $tax_class_id
     * @param product object
     *
     * @return float
     */
    protected function processPrice($price, $tax_class_id, $pro) {
        if($price < 0){$price = 0;}else{$price = $price;}
        $config = Mage::helper('klevu_search/config');
		if (($pro->getData('type_id') == Mage_Catalog_Model_Product_Type::TYPE_GROUPED || $pro->getData('type_id')==Mage_Catalog_Model_Product_Type::TYPE_BUNDLE )) {
		    return $this->convertPrice($price);	
		}
        if($config->isTaxEnabled($this->getStore()->getId())) {
           return $this->convertPrice(Mage::helper("tax")->getPrice($pro, $price, true, null, null, null, $this->getStore(),false));
        } else {
            return $this->convertPrice($price);
        }
    }

    /**
     * Return the "Manage Stock" flag for the current store.
     *
     * @return int
     */
    protected function getStoreManageStock() {
        if (!$this->hasData('store_manage_stock')) {
            $this->setData('store_manage_stock', intval(Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK, $this->getStore())));
        }

        return $this->getData('store_manage_stock');
    }

    /**
     * Return the "Display Out of Stock Products".
     *
     * @return bool
     */
    protected function getShowOutOfStock() {
        if (!$this->hasData('show_out_of_stock')) {
            $this->setData('show_out_of_stock', Mage::helper('cataloginventory')->isShowOutOfStock());
        }

        return $this->getData('show_out_of_stock');
    }

    /**
     * Check if the Test Mode is enabled for the current store.
     *
     * @return int 1 if Test Mode is enabled, 0 otherwise.
     */
    protected function isTestModeEnabled() {
        if (!$this->hasData("test_mode_enabled")) {
            $test_mode = Mage::helper("klevu_search/config")->isTestModeEnabled($this->getStore());
            $test_mode = ($test_mode) ? 1 : 0;
            $this->setData("test_mode_enabled", $test_mode);
        }

        return $this->getData("test_mode_enabled");
    }

    /**
     * Check if product price includes tax for the current store.
     *
     * @return bool
     */
    protected function priceIncludesTax() {
        if (!$this->hasData("price_includes_tax")) {
            $this->setData("price_includes_tax", Mage::getModel("tax/config")->priceIncludesTax($this->getStore()));
        }

        return $this->getData("price_includes_tax");
    }

    /**
     * Check if product prices should include tax when synced for the current store.
     *
     * @return bool
     */
    protected  function usePriceInclTax() {
        if (!$this->hasData("use_price_incl_tax")) {
            // Include tax in prices in all cases except when
            // catalog prices exclude tax
            $value = true;

            if (Mage::getModel("tax/config")->getPriceDisplayType($this->getStore()) == Mage_Tax_Model_Config::DISPLAY_TYPE_EXCLUDING_TAX) {
                $value = false;
            }

            $this->setData("use_price_incl_tax", $value);
        }

        return $this->getData("use_price_incl_tax");
    }

    /**
     * Remove any session specific data.
     *
     * @return $this
     */
    protected function reset() {
        $this->unsetData('session_id');
        $this->unsetData('store');
        $this->unsetData('attribute_map');
        $this->unsetData('placeholder_image');
        $this->unsetData('category_paths');
        $this->unsetData('attribute_data');
        $this->unsetData('store_manage_stock');
        $this->unsetData('test_mode_enabled');
        $this->unsetData('tax_rates');
        $this->unsetData('price_includes_tax');
        $this->unsetData('use_price_incl_tax');

        return $this;
    }

    /**
     * Create an Adminhtml notification for Product Sync, overwriting any
     * existing ones. If a store is specified, creates a notification specific
     * to that store, separate from the main Product Sync notification.
     *
     * Overwrites any existing notifications for product sync.
     *
     * @param $message
     * @param Mage_Core_Model_Store|null $store
     *
     * @return $this
     */
    public function notify($message, $store = null) {
        $type = ($store === null) ? static::NOTIFICATION_GLOBAL_TYPE : static::NOTIFICATION_STORE_TYPE_PREFIX . $store->getId();
        /** @var Klevu_Search_Model_Notification $notification */
        $notification = Mage::getResourceModel('klevu_search/notification_collection')
            ->addFieldToFilter("type", array('eq' => $type))
            ->getFirstItem();

        $notification->addData(array(
            'type'    => $type,
            'date'    => Mage::getModel('core/date')->timestamp(),
            'message' => $message
        ));

        $notification->save();

        return $this;
    }

    /**
     * Delete Adminhtml notifications for Product Sync. If a store is specified,
     * deletes the notifications for the specific store.
     *
     * @param Mage_Core_Model_Store|null $store
     * @return $this
     */
    protected function deleteNotifications($store = null) {
        $type = ($store === null) ? static::NOTIFICATION_GLOBAL_TYPE : static::NOTIFICATION_STORE_TYPE_PREFIX . $store->getId();
        $this->getConnection()->delete($this->getTableName('klevu_search/notification'), array("type = ?" => $type));
        return $this;
    }

      
    /**
     * Generate batch for thumbnail image
     * @param $image
     * @return $this
     */    
        
    public function thumbImage($image)
        {
            try {
                $baseImageUrl = Mage::getBaseDir('media').DS."catalog".DS."product".$image;
                if(file_exists($baseImageUrl)) {
                    list($width, $height, $type, $attr)=getimagesize($baseImageUrl); 
                    if($width > 200 && $height > 200) {
                        $imageResized = Mage::getBaseDir('media').DS."klevu_images".$image;
                        if(!file_exists($imageResized)) {
                            $this->thumbImageObj($baseImageUrl,$imageResized);
                        }
                    }
                }
            }catch(Exception $e) {
                 Mage::helper('klevu_search')->log(Zend_Log::DEBUG, sprintf("Image Error:\n%s", $e->getMessage()));
            }
    }
        
    /**
     * Generate thumb image
     * @param $imageUrl 
     * @param $imageResized
     * @return $this
     */  
    public function thumbImageObj($imageUrl,$imageResized)
    {
        $imageObj = new Varien_Image($imageUrl);
        $imageObj->constrainOnly(TRUE);
        $imageObj->keepAspectRatio(TRUE);
        $imageObj->keepFrame(FALSE);
        $imageObj->keepTransparency(true);
        $imageObj->backgroundColor(array(255, 255, 255));
        $imageObj->resize(200, 200);
        $imageObj->save($imageResized);
    }
    
    
    /**
     * Get ida for debugs
     * @return $this
     */    
    public function debugsIds()
    {
        $select = $this->getConnection()->select()
                ->from($this->getTableName("catalog_product_entity"), array('entity_id','updated_at'))->limit(500)->order('updated_at');
        $data = $this->getConnection()->fetchAll($select);
        return $data;
    }
    
    /**
     * Get api for debugs
     * @return $this
     */    
    public function getApiDebug()
    {
        $configs = Mage::getModel('core/config_data')->getCollection()
                  ->addFieldToFilter('path', array("like" => "%rest_api_key%"))->load();
        $data = $configs->getData();
        return $data[0]['value'];
    }
    
    /**
     * Run cron externally for debug using js api
     * @param $js_api
     * @return $this
     */    
    public function sheduleCronExteranally($rest_api) {
        $configs = Mage::getModel('core/config_data')->getCollection()
                ->addFieldToFilter('value', array("like" => "%$rest_api%"))->load();
        $data = $configs->getData();
        if(!empty($data[0]['scope_id'])){
            $store = Mage::app()->getStore($data[0]['scope_id']);
            Mage::getModel('klevu_search/product_sync')
            ->markAllProductsForUpdate($store)
            ->schedule();
        }
    }
    
    
    /**
     * Delete test mode data from product sync
     * @return $this
     */ 
    public function deleteTestmodeData($store) {
        $condition = array("store_id"=> $store->getId());
        $this->getConnection()->delete($this->getTableName("klevu_search/product_sync"),$condition);    
    }
    
    /**
     * Exchange key and value for test mode 
     * @return $this
     */ 
    public function removeTestMode() {
        $stores = Mage::app()->getStores();
        foreach ($stores as $store) {
            $test_mode = Mage::helper("klevu_search/config")->isTestModeEnabled($store);
            if(Mage::helper('klevu_search/config')->isExtensionConfigured($store)) {
                if($test_mode){
                    $final_test_rest_api =   Mage::getStoreConfig('klevu_search/general/rest_api_key', $store);
                    $final_rest_api =   Mage::getStoreConfig('klevu_search/general/test_rest_api_key', $store);
                    Mage::helper('klevu_search/config')->setStoreConfig('klevu_search/general/js_api_key', Mage::getStoreConfig('klevu_search/general/test_js_api_key', $store), $store);
                    Mage::helper('klevu_search/config')->setStoreConfig('klevu_search/general/rest_api_key', Mage::getStoreConfig('klevu_search/general/test_rest_api_key', $store), $store);
                    $test_hostname = Mage::getStoreConfig('klevu_search/general/test_hostname', $store);
                    if(!empty($test_hostname)) {
                        Mage::helper('klevu_search/config')->setStoreConfig('klevu_search/general/hostname', Mage::getStoreConfig('klevu_search/general/test_hostname', $store), $store);
                        Mage::helper('klevu_search/config')->setStoreConfig('klevu_search/general/cloud_search_url', Mage::getStoreConfig('klevu_search/general/test_cloud_search_url', $store), $store);
                        Mage::helper('klevu_search/config')->setStoreConfig('klevu_search/general/analytics_url', Mage::getStoreConfig('klevu_search/general/test_analytics_url', $store), $store);
                        Mage::helper('klevu_search/config')->setStoreConfig('klevu_search/general/js_url', Mage::getStoreConfig('klevu_search/general/test_js_url', $store), $store);
                    }
                    Mage::helper("klevu_search/config")->setTestModeEnabledFlag(0, $store);
                    //send responsce in kmc
                    $response = Mage::getModel("klevu_search/api_action_removetestmode")->removeTestMode(array('liveRestApiKey'=>$final_rest_api,'testRestApiKey'=>$final_test_rest_api));
                    if($response->getMessage()=="success") {
                        $this->log(Zend_Log::INFO, $response->getMessage());
                    }
                    // delete prodcut entry for test mode 
                    Mage::getModel('klevu_search/product_sync')->deleteTestmodeData($store);
                    //schedual cron for all prodcuts
                        Mage::getModel('klevu_search/product_sync')
                        ->markAllProductsForUpdate($store)
                        ->schedule();
                }
            }   
        }
    }
    /**
     * Get special price expire date attribute value  
     * @return array
     */ 
    public function getExpiryDateAttributeId() {
        $query = $this->getConnection()->select()
                    ->from($this->getTableName("eav_attribute"), array('attribute_id'))
                    ->where('attribute_code=?','special_to_date');
        $data = $query->query()->fetchAll();
        return $data[0]['attribute_id'];
    }
    
    /**
     * Get prodcuts ids which have expiry date gone and update next day
     * @return array
     */ 
    public function getExpirySaleProductsIds() {
        $attribute_id = $this->getExpiryDateAttributeId();
        $current_date = date_create("now")->format("Y-m-d");
        $query = $this->getConnection()->select()
                    ->from($this->getTableName("catalog_product_entity_datetime"), array('entity_id'))
                    ->where("attribute_id=:attribute_id AND DATE_ADD(value,INTERVAL 1 DAY)=:current_date")
                    ->bind(array(
                            'attribute_id' => $attribute_id,
                            'current_date' => $current_date
                    ));
        $data = $this->getConnection()->fetchAll($query, $query->getBind());
        $pro_ids = array();
        foreach($data as $key => $value)
        {
            $pro_ids[] = $value['entity_id'];
        }
        return $pro_ids;
       
    }
    
    
    /**
     * Get prodcuts ids which have expiry date gone and update next day
     * @return array
     */ 
    public function getCatalogRuleProductsIds() {
        $attribute_id = $this->getExpiryDateAttributeId();
        $current_date = date_create("now")->format("Y-m-d");
        $query = $this->getConnection()->select()
                    ->from($this->getTableName("catalog_product_entity_datetime"), array('entity_id'))
                    ->where("attribute_id=:attribute_id AND DATE_ADD(value,INTERVAL 1 DAY)=:current_date")
                    ->bind(array(
                            'attribute_id' => $attribute_id,
                            'current_date' => $current_date
                    ));
        $data = $this->getConnection()->fetchAll($query, $query->getBind());
        $pro_ids = array();
        foreach($data as $key => $value)
        {
            $pro_ids[] = $value['entity_id'];
        }
        return $pro_ids;
       
    }
   
    
    /**
     * if special to price date expire then make that product for update
     * @return $this
     */ 
    public function markProductForUpdate(){
        try {
            $special_pro_ids = $this->getExpirySaleProductsIds();
            if(!empty($special_pro_ids)) {
                $this->updateSpecificProductIds($special_pro_ids);
            }
            
        } catch(Exception $e) {
                Mage::helper('klevu_search')->log(Zend_Log::CRIT, sprintf("Exception thrown in markforupdate %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
        }
    }
    
    /**
     * Mark product ids for update
     *
     * @param array ids
     *
     * @return 
     */ 
    public function updateSpecificProductIds($ids)
    {
        $resource = Mage::getSingleton('core/resource');
        $pro_ids = implode(',', $ids);
        $where = sprintf("(product_id IN(%s) OR parent_id IN(%s)) AND %s", $pro_ids,$pro_ids,$this->getConnection()->quoteInto('type = ?',"products"));
         
        $resource->getConnection('core_write')->update(
        $resource->getTableName('klevu_search/product_sync'),
                array('last_synced_at' => '0'),
                $where
                );
   }
   
    /**
     * Update all product ids rating attribute
     *
     * @param string store
     *
     * @return  $this
     */ 
    public function updateProductsRating($store)
    {

        $entity_type = Mage::getSingleton("eav/entity_type")->loadByCode("catalog_product");
        $entity_typeid = $entity_type->getId();
        $attributecollection = Mage::getModel("eav/entity_attribute")->getCollection()->addFieldToFilter("entity_type_id", $entity_typeid)->addFieldToFilter("attribute_code", "rating");
        if(count($attributecollection) > 0) {
            $sumColumn = new Zend_Db_Expr("AVG(rating_vote.{$this->getConnection()->quoteIdentifier('percent')})");
            $select = $this->getConnection()->select()
                ->from(array('rating_vote' => $this->getTableName('rating/rating_option_vote')),
                    array(
                        'entity_pk_value' => 'rating_vote.entity_pk_value',
                        'sum'             => $sumColumn,
                        ))
                ->join(array('review' => $this->getTableName('review/review')),
                    'rating_vote.review_id=review.review_id',
                    array())
                ->joinLeft(array('review_store' => $this->getTableName('review/review_store')),
                    'rating_vote.review_id=review_store.review_id',
                    array('review_store.store_id'))
                ->join(array('rating_store' => $this->getTableName('rating/rating_store')),
                    'rating_store.rating_id = rating_vote.rating_id AND rating_store.store_id = review_store.store_id',
                    array())
                ->join(array('review_status' => $this->getTableName('review/review_status')),
                    'review.status_id = review_status.status_id',
                    array())
                ->where('review_status.status_code = :status_code AND rating_store.store_id = :storeId')
                ->group('rating_vote.entity_pk_value')
                ->group('review_store.store_id');
            $bind = array('status_code' => "Approved",'storeId' => $store->getId());
            $data_ratings = $this->getConnection()->fetchAll($select,$bind);
            $allStores = Mage::app()->getStores();
            foreach($data_ratings as $key => $value)
            {
                if(count($allStores) > 1) {
                    Mage::getModel('catalog/product_action')->updateAttributes(array($value['entity_pk_value']), array('rating'=>0),0);
                }
                Mage::getModel('catalog/product_action')->updateAttributes(array($value['entity_pk_value']), array('rating'=>$value['sum']), $store->getId());
                Mage::helper('klevu_search')->log(Zend_Log::DEBUG, sprintf("Rating is updated for product id %s",$value['entity_pk_value']));
            }
        }
   }
   
    /**
     * Convert percent to rating star
     *
     * @param int percentage
     *
     * @return float
     */
    public function convertToRatingStar($percentage) {
        if(!empty($percentage) && $percentage!=0) {
            $start = $percentage * 5;
            return round($start/100, 2);
        } else {
            return;
        }
    }
    
    /**
     * Perform Category Sync on any configured stores, adding new categories, updating modified and
     * deleting removed category since last sync.
     */
    public function runCategory($store)
    {
            $isActiveAttributeId =  Mage::helper("klevu_search")->getIsActiveAttributeId();
			$isExcludeAttributeId = Mage::helper("klevu_search")->getIsExcludeAttributeId();
            $this->log(Zend_Log::INFO, sprintf("Starting sync for category %s (%s).", $store->getWebsite()->getName() , $store->getName()));
            $rootId = $this->getStore()->getRootCategoryId();
            $rootStoreCategory = "1/$rootId/";
            $actions = array(
                    'delete' => $this->getConnection()
                        ->select()
                        /*
                         * Select synced categories in the current store/mode that 
                         * are no longer enabled
                         */
                        ->from(
                                    array('k' => $this->getTableName("klevu_search/product_sync")),
                                    array('category_id' => "k.product_id")
                                   
                        )
                        ->joinLeft(
                                    array('ci' => $this->getTableName("catalog_category_entity_int")),
                                    "k.product_id = ci.entity_id AND ci.attribute_id = :is_active",
                                    ""
                                )
						->joinLeft(
                                    array('ex' => $this->getTableName("catalog_category_entity_int")),
                                    "k.product_id = ex.entity_id AND ex.attribute_id = :is_exclude",
                                    ""
                                )
                        ->where("k.type = :type AND (ci.value = 0 OR ex.value = 1 OR k.product_id NOT IN ?)",
                                $this->getConnection()
                                ->select()
                                ->from(
                                    array('i' => $this->getTableName("catalog_category_entity_int")),
                                    array('category_id' => "i.entity_id")
                                )
                        )
                        ->group(array('k.product_id', 'k.parent_id'))
                        ->bind(array(
                            'type'=>"categories",
                            'is_active' => $isActiveAttributeId,
							'is_exclude' => $isExcludeAttributeId,
                        )),
                    'update' => 
                            $this->getConnection()
                                ->select()
                                /*
                                 * Select categories for the current store/mode
                                 * have been updated since last sync.
                                 */
                                 ->from(
                                    array('k' => $this->getTableName("klevu_search/product_sync")),
                                    array('category_id' => "k.product_id")
                                   
                                )
                                ->join(
                                    array('ce' => $this->getTableName("catalog_category_entity")),
                                    "k.product_id = ce.entity_id",
                                    ""
                                )
                                ->where("(k.type = :type) AND k.test_mode = :test_mode AND (k.store_id = :store_id) AND (ce.updated_at > k.last_synced_at)")
                                ->bind(array(
                                    'store_id' => $store->getId(),
                                    'type'=> "categories",
                                    'test_mode' => $this->isTestModeEnabled(),
                                )),
                    'add' =>  $this->getConnection()
                                ->select()
                                /*
                                 * Select categories for the current store/mode
                                 * have been updated since last sync.
                                 */
                                ->from(
                                    array('c' => $this->getTableName("catalog_category_entity")),
                                    array('category_id' => "c.entity_id")
                                )
                                ->join(
                                    array('ci' => $this->getTableName("catalog_category_entity_int")),
                                    "c.entity_id = ci.entity_id AND ci.attribute_id = :is_active AND ci.value = 1",
                                    ""
                                )
								->joinLeft(
                                    array('ex' => $this->getTableName("catalog_category_entity_int")),
                                    "ci.entity_id = ex.entity_id AND ex.attribute_id = :is_exclude",
                                    ""
                                )
                                ->joinLeft(
                                    array('k' => $this->getTableName("klevu_search/product_sync")),
                                    "ci.entity_id = k.product_id AND k.store_id = :store_id AND k.test_mode = :test_mode AND k.type = :type",
                                    ""
                                )
                                ->where("k.product_id IS NULL AND (ex.value IS NULL OR ex.value = 0)")
                                ->where("c.path LIKE ?","{$rootStoreCategory}%")
								->group(array('c.entity_id'))
                        ->bind(array(
                            'type' => "categories",
                            'store_id' => $store->getId(),
                            'is_active' => $isActiveAttributeId,
                            'test_mode' => $this->isTestModeEnabled(),
							'is_exclude' => $isExcludeAttributeId,
                        )),
                );
            $errors = 0;
            foreach($actions as $action => $statement) {
                if ($this->rescheduleIfOutOfMemory()) {
                    return;
                }
                $method = $action . "Category";
                $category_pages = $this->getConnection()->fetchAll($statement, $statement->getBind());
                $total = count($category_pages);
                $this->log(Zend_Log::INFO, sprintf("Found %d category Pages to %s.", $total, $action));
                $pages = ceil($total / static ::RECORDS_PER_PAGE);
                for ($page = 1; $page <= $pages; $page++) {
                    if ($this->rescheduleIfOutOfMemory()) {
                        return;
                    }
                    $offset = ($page - 1) * static ::RECORDS_PER_PAGE;
                    $result = $this->$method(array_slice($category_pages, $offset, static ::RECORDS_PER_PAGE));
                    if ($result !== true) {
                        $errors++;
                        $this->log(Zend_Log::ERR, sprintf("Errors occurred while attempting to %s categories pages %d - %d: %s", $action, $offset + 1, ($offset + static ::RECORDS_PER_PAGE <= $total) ? $offset + static ::RECORDS_PER_PAGE : $total, $result));
                    }
                }
            }
            $this->log(Zend_Log::INFO, sprintf("Finished category page sync for %s (%s).", $store->getWebsite()->getName() , $store->getName()));
    }
    /**
     * Add the given Categories to Klevu Search. Returns true if the operation was successful,
     * or the error message if it failed.
     *
     * @param array $data List of Categories to add. Each element should be an array
     *                    containing an element with "category_id" as the key and category id as
     *                    the value.
     *
     * @return bool|string
     */
    protected function addCategory(array $data)
    {
		Mage::getSingleton('core/session')->setKlevuFailedFlag(0);
        $total = count($data);
        $data = $this->addcategoryData($data);
        $response = Mage::getModel('klevu_search/api_action_addrecords')->setStore($this->getStore())->execute(array(
            'sessionId' => $this->getSessionId() ,
            'records' => $data
        ));
        if ($response->isSuccessful()) {
			Mage::getSingleton('core/session')->setKlevuFailedFlag(0);
            $skipped_record_ids = array();
            if ($skipped_records = $response->getSkippedRecords()) {
                $skipped_record_ids = array_flip($skipped_records["index"]);
            }
            $sync_time = Mage::helper("klevu_search/compat")->now();
            foreach($data as $i => & $record) {
                if (isset($skipped_record_ids[$i])) {
                    unset($data[$i]);
                    continue;
                }
                $ids[$i] = explode("_", $data[$i]['id']);
                $record = array(
                    $ids[$i][1],
                    0,
                    $this->getStore()->getId() ,
                    $this->isTestModeEnabled() ,
                    $sync_time,
                    "categories"
                );
            }
            $this->getConnection()->insertArray($this->getTableName('klevu_search/product_sync') , array(
                "product_id",
                "parent_id",
                "store_id",
                "test_mode",
                "last_synced_at",
                "type"
            ) , $data);
            $skipped_count = count($skipped_record_ids);
            if ($skipped_count > 0) {
                return sprintf("%d category%s failed (%s)", $skipped_count, ($skipped_count > 1) ? "s" : "", implode(", ", $skipped_records["messages"]));
            }
            else {
                return true;
            }
        }
        else {
            Mage::getSingleton('core/session')->setKlevuFailedFlag(1);
			return sprintf("%d category%s failed (%s)", $total, ($total > 1) ? "s" : "", $response->getMessage());
			
			
        }
    }
    /**
     * Add the Category Sync data to each Category in the given list. Updates the given
     * list directly to save memory.
     *
     * @param array $categories An array of categories. Each element should be an array with
     *                        containing an element with "id" as the key and the Category
     *                        ID as the value.
     *
     * @return $this
     */
    protected function addcategoryData(&$pages)
    {
        $config = Mage::helper('klevu_search/config');
        $category_ids = array();
        foreach($pages as $key => $value) {
            $category_ids[] = $value["category_id"];
        }
        $category_data = Mage::getModel('catalog/category')->getCollection()->addAttributeToSelect("*")->addFieldToFilter('entity_id', array(
            'in' => $category_ids
        ));
        foreach($category_data as $category) {
            $value["id"] = "categoryid_" . $category->getId();
            $value["name"] = $category->getName();
            $value["desc"] = strip_tags($category->getDescription());
            $value["url"] = $category->getURL();
            $value["metaDesc"] = $category->getMetaDescription() . $category->getMetaKeywords();
            $value["shortDesc"] = substr(strip_tags($category->getDescription()) , 0, 200);
            $value["listCategory"] = "KLEVU_CATEGORY";
            $value["category"] = "Categories";
            $value["salePrice"] = 0;
            $value["currency"] = "USD";
            $value["inStock"] = "yes";
            $category_data_new[] = $value;
        }
        return $category_data_new;
    }
    /**
     * Update the given categories on Klevu Search. Returns true if the operation was successful,
     * or the error message if it failed.
     *
     * @param array $data List of categories to update. Each element should be an array
     *                    containing an element with "category_id" as the key and category id as
     *                    the value
     *
     * @return bool|string
     */
    protected function updateCategory(array $data)
    {
		Mage::getSingleton('core/session')->setKlevuFailedFlag(0);
        $total = count($data);
        $data = $this->addcategoryData($data);
        $response = Mage::getModel('klevu_search/api_action_updaterecords')->setStore($this->getStore())->execute(array(
            'sessionId' => $this->getSessionId() ,
            'records' => $data
        ));
        if ($response->isSuccessful()) {
            $helper = Mage::helper('klevu_search');
            $connection = $this->getConnection();
            $skipped_record_ids = array();
            if ($skipped_records = $response->getSkippedRecords()) {
                $skipped_record_ids = array_flip($skipped_records["index"]);
            }
            $where = array();
            for ($i = 0; $i < count($data); $i++) {
                if (isset($skipped_record_ids[$i])) {
                    continue;
                }
                $ids[$i] = explode("_", $data[$i]['id']);
                $where[] = sprintf("(%s AND %s AND %s)", $connection->quoteInto("product_id = ?", $ids[$i][1]) , $connection->quoteInto("parent_id = ?", 0) , $connection->quoteInto("type = ?", "categories"));
            }
            $where = sprintf("(%s) AND (%s) AND (%s)", $connection->quoteInto("store_id = ?", $this->getStore()->getId()) , $connection->quoteInto("test_mode = ?", $this->isTestModeEnabled()) , implode(" OR ", $where));
            $this->getConnection()->update($this->getTableName('klevu_search/product_sync') , array(
                'last_synced_at' => Mage::helper("klevu_search/compat")->now()
            ) , $where);
            $skipped_count = count($skipped_record_ids);
            if ($skipped_count > 0) {
                return sprintf("%d category%s failed (%s)", $skipped_count, ($skipped_count > 1) ? "s" : "", implode(", ", $skipped_records["messages"]));
            }
            else {
                return true;
            }
        }
        else {
			Mage::getSingleton('core/session')->setKlevuFailedFlag(1);
            return sprintf("%d category%s failed (%s)", $total, ($total > 1) ? "s" : "", $response->getMessage());
        }
    }
    /**
     * Delete the given categories from Klevu Search. Returns true if the operation was
     * successful, or the error message if the operation failed.
     *
     * @param array $data List of categories to delete. Each element should be an array
     *                    containing an element with "category_id" as the key and category id as
     *                    the value.
     *
     * @return bool|string
     */
    protected function deleteCategory(array $data)
    {
		$baseDomain = $this->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK,true);
		
		foreach($data as $key => $value){
			$data[$key]['url'] = $baseDomain; 
		}
        $total = count($data);
        $response = Mage::getModel('klevu_search/api_action_deleterecords')->setStore($this->getStore())->execute(array(
            'sessionId' => $this->getSessionId() ,
            'records' => array_map(function ($v)
            {
                return array(
                    'id' => "categoryid_" . $v['category_id'],
					'url' => $v['url']
                );
            }
            , $data)
        ));
        if ($response->isSuccessful()) {
            $connection = $this->getConnection();
            $select = $connection->select()->from(array(
                'k' => $this->getTableName("klevu_search/product_sync")
            ))->where("k.store_id = ?", $this->getStore()->getId())->where("k.type = ?", "categories")->where("k.test_mode = ?", $this->isTestModeEnabled());
            $skipped_record_ids = array();
            if ($skipped_records = $response->getSkippedRecords()) {
                $skipped_record_ids = array_flip($skipped_records["index"]);
            }
            $or_where = array();
            for ($i = 0; $i < count($data); $i++) {
                if (isset($skipped_record_ids[$i])) {
                    continue;
                }
                $or_where[] = sprintf("(%s)", $connection->quoteInto("k.product_id = ?", $data[$i]['category_id']));
            }
            $select->where(implode(" OR ", $or_where));
            $connection->query($select->deleteFromSelect("k"));
            $skipped_count = count($skipped_record_ids);
            if ($skipped_count > 0) {
                return sprintf("%d category%s failed (%s)", $skipped_count, ($skipped_count > 1) ? "s" : "", implode(", ", $skipped_records["messages"]));
            }
            else {
                return true;
            }
        }
        else {
            return sprintf("%d category%s failed (%s)", $total, ($total > 1) ? "s" : "", $response->getMessage());
        }
    }
    

    
    // Get features for account
    public function getFeatures()
    {           
        if (strlen($code = Mage::app()->getRequest()->getParam('store'))) { // store level
            $code = Mage::app()->getRequest()->getParam('store');
            if (!$this->_klevu_features_response) {
                $store = Mage::getModel('core/store')->load($code);
                $store_id = $store->getId();
                $config = Mage::helper('klevu_search/config');
                $restapi = $config->getRestApiKey($store_id);
                $param =  array("restApiKey" => $restapi);
                $this->_klevu_features_response = Mage::helper('klevu_search/config')->executeFeatures($restapi,$store);
            }
            return $this->_klevu_features_response;
        }

    }
    
    // Get all products for update
    public function catalogruleUpdateinfo(){
        $timestamp_after = strtotime("+1 day",strtotime(date_create("now")->format("Y-m-d")));
        $timestamp_before = strtotime("-1 day",strtotime(date_create("now")->format("Y-m-d")));
        $query = $this->getConnection()->select()
                    ->from($this->getTableName("catalogrule_product"), array('product_id'))
                    ->where("customer_group_id=:customer_group_id AND ((from_time BETWEEN :timestamp_before AND :timestamp_after) OR (to_time BETWEEN :timestamp_before AND :timestamp_after))")
                    ->bind(array(
                            'customer_group_id' => Mage_Customer_Model_Group::NOT_LOGGED_IN_ID,
                            'timestamp_before' => $timestamp_before,
                            'timestamp_after' => $timestamp_after
                    ));

        $data = $this->getConnection()->fetchAll($query, $query->getBind());

        $pro_ids = array();

        foreach($data as $key => $value)
        {
            $pro_ids[] = $value['product_id'];
        }
        if(!empty($pro_ids)) {
            $this->updateSpecificProductIds($pro_ids);
        }
    }
    
    /**
     * Get the klevu cron entry which is running mode
     * @return int
     */
    public function getKlevuCronStatus(){
        $collection = Mage::getResourceModel('cron/schedule_collection')
        ->addFieldToFilter("job_code", $this->getJobCode())
        ->addFieldToFilter("status", Mage_Cron_Model_Schedule::STATUS_RUNNING);
        if($collection->getSize()){
            $data = $collection->getData();
            $url = Mage::getModel('adminhtml/url')->getUrl("adminhtml/klevu_search/clear_klevu_cron");
            return Mage_Cron_Model_Schedule::STATUS_RUNNING." Since ".$data[0]['executed_at']." <a href='".$url."'>Clear Klevu Cron</a>";
        } else {
            $collection = Mage::getResourceModel('cron/schedule_collection')
            ->addFieldToFilter("job_code", $this->getJobCode())
            ->addFieldToFilter("status",Mage_Cron_Model_Schedule::STATUS_SUCCESS)
            ->setOrder('finished_at', 'desc');
            if($collection->getSize()){
                $data = $collection->getData();
                return Mage_Cron_Model_Schedule::STATUS_SUCCESS." ".$data[0]["finished_at"];
            }
        }
        return;
    }
    
    /**
     * Remove the cron which is in running state
     * @return void
     */
    public function clearKlevuCron(){
        $condition = array();
        $condition[] = $this->getConnection()->quoteInto('status = ?', Mage_Cron_Model_Schedule::STATUS_RUNNING);   
        $condition[] = $this->getConnection()->quoteInto('job_code = ?',$this->getJobCode());
        $this->getConnection()->delete($this->getTableName("cron_schedule"),$condition);   
    }
	
    /**
     * This function created to sync All data store wise
     *
     * @param $storeCodesToSync Array of store codes
     * @throws Exception
     */
    public function syncStores($storeCodesToSync) {

        try {

            // reset the flag for fail message
            Mage::getSingleton('core/session')->setKlevuFailedFlag(0);

            // check the status of indexing when collection method selected to sync data
            $config = Mage::helper('klevu_search/config');

            $stores = Mage::app()->getStores();
            foreach ($stores as $store) {
                if(in_array($store->getCode(),$storeCodesToSync)){
                    $this->markAllProductsForUpdate($store->getId());
                }
            }

            /* update boosting rule event */
            try {
                Mage::helper('klevu_search')->log(Zend_Log::INFO, "Boosting rule update is started");
                Mage::dispatchEvent('update_rule_of_products', array());
            } catch(Exception $e) {
                Mage::helper('klevu_search')->log(Zend_Log::WARN, "Unable to update boosting rule");
            }

            // Sync Data only for selected store from config wizard
            $firstSync = Mage::getSingleton('klevu_search/session')->getFirstSync();

            if(!empty($firstSync)){
                /** @var Mage_Core_Model_Store $store */
                $this->reset();
                $onestore = Mage::app()->getStore($firstSync);
                if (!$this->setupSession($onestore)) {
                    return;
                }
                $this->syncData($onestore);
                return;
            }

            if ($this->isRunning(2)) {
                // Stop if another copy is already running
                $this->log(Zend_Log::INFO, "Stopping because another copy is already running.");
                return;
            }

            $stores = Mage::app()->getStores();
            $syncedStores = array();
            foreach ($stores as $store) {
                if(in_array($store->getCode(),$storeCodesToSync)){
                    $this->reset();
                    if (!$this->setupSession($store)) {
                        continue;
                    }
                    $this->syncData($store);
                    $syncedStores[] = $store->getCode();
                }
            }

            // update rating flag after all store view sync
            $rating_upgrade_flag = $config->getRatingUpgradeFlag();
            if($rating_upgrade_flag==0) {
                $config->saveRatingUpgradeFlag(1);
            }
            return $syncedStores;
        } catch (Exception $e) {
            // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
            Mage::helper('klevu_search')->log(Zend_Log::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
            throw $e;
        }
    }
	
}
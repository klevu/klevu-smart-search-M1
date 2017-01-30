<?php

/**
 * Class Klevu_Search_Model_Order_Sync
 * @method Varien_Db_Adapter_Interface getConnection()
 */
class Klevu_Search_Model_Order_Sync extends Klevu_Search_Model_Sync {

    const NOTIFICATION_TYPE = "order_sync";

    public function _construct() {
        parent::_construct();

        $this->addData(array(
            "connection" => Mage::getModel("core/resource")->getConnection("core_write")
        ));
    }

    public function getJobCode() {
        return "klevu_search_order_sync";
    }

    /**
     * Add the items from the given order to the Order Sync queue. Does nothing if
     * Order Sync is disabled for the store that the order was placed in.
     *
     * @param Mage_Sales_Model_Order $order
     * @param bool                   $force Skip enabled check
     *
     * @return $this
     */
    public function addOrderToQueue(Mage_Sales_Model_Order $order, $force = false) {
        if (!$this->isEnabled($order->getStoreId()) && !$force) {
            return $this;
        }

        $items = array();
		$order_date = Mage::helper("klevu_search/compat")->now();
		$session_id = session_id();
		$ip_address = Mage::helper("klevu_search")->getIp();
		$order_email = 'unknown';
		if($order->getCustomerId()){
           $order_email = $order->getCustomer()->getEmail(); //logged in customer
		} else{
		   $order_email = $order->getBillingAddress()->getEmail(); //not logged in customer
		}
        foreach ($order->getAllVisibleItems() as $item) {
            /** @var Mage_Sales_Model_Order_Item $item */

            // For configurable products add children items only, for all other products add parents
            if ($item->getProductType() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
                foreach ($item->getChildrenItems() as $child) {
                    if($child->getId()!=null) {
                        $items[] =  array($child->getId(),$session_id,$ip_address,$order_date,$order_email);
                    }
                }
            } else {
                if($item->getId()!=null) {
                        $items[] =  array($item->getId(),$session_id,$ip_address,$order_date,$order_email);
                }
                
            }
        }

        // in case of multiple addresses used for shipping
        // its possible that items object here is empty
        // if so, we do not add to the item.
        if(!empty($items)) {
           $this->addItemsToQueue($items);
        }

        return $this;
    }

    /**
     * Clear the Order Sync queue for the given store. If no store is given, clears
     * the queue for all stores.
     *
     * @param Mage_Core_Model_Store|int|null $store
     *
     * @return int
     */
    public function clearQueue($store = null) {
        $select = $this->getConnection()
            ->select()
            ->from(array("k" => $this->getTableName("klevu_search/order_sync")));

        if ($store) {
            $store = Mage::app()->getStore($store);
            $select
                ->join(
                    array("i" => $this->getTableName("sales/order_item")),
                    "k.order_item_id = i.item_id",
                    ""
                )
                ->where("i.store_id = ?", $store->getId());
        }

        $result = $this->getConnection()->query($select->deleteFromSelect("k"));
        return $result->rowCount();
    }

    public function run() {
        try {
            if ($this->isRunning(2)) {
                // Stop if another copy is already running
                $this->log(Zend_Log::INFO, "Another copy is already running. Stopped.");
                return;
            }
            
            $stores = Mage::app()->getStores();
            foreach ($stores as $store) {
                if(Mage::helper("klevu_search/config")->isOrderSyncEnabled($store->getId())) {
                    $this->log(Zend_Log::INFO, "Starting sync.");
                    $items_synced = 0;
                    $errors = 0;

                    $item = Mage::getModel("sales/order_item");

                    $stmt = $this->getConnection()->query($this->getSyncQueueSelect());
					$itemsToSend = $stmt->fetchAll();
                    foreach ($itemsToSend as $key => $value) {
                        if ($this->rescheduleIfOutOfMemory()) {
                            return;
                        }

                        $item->setData(array());
                        $item->load($value['order_item_id']);

                        if ($item->getId()) {
                            if ($this->isEnabled($item->getStoreId())) {
                                if ($this->getApiKey($item->getStoreId())) {
                                        $result = $this->sync($item,$value['klevu_session_id'],$value['ip_address'],$value['date'],$value['email']);
                                        if ($result === true) {
                                            $this->removeItemFromQueue($value['order_item_id']);
                                            $items_synced++;
                                        } else {
                                            $this->log(Zend_Log::INFO, sprintf("Skipped order item %d: %s", $value['order_item_id'], $result));
                                            $errors++;
                                        }
                                }
                            } else {
                                $this->log(Zend_Log::ERR, sprintf("Skipped item %d: Order Sync is not enabled for this store.", $value['order_item_id']));
                                $this->removeItemFromQueue($value['order_item_id']);
                            }
                        } else {
                            $this->log(Zend_Log::ERR, sprintf("Order item %d does not exist: Removed from sync!", $value['order_item_id']));
                            $this->removeItemFromQueue($value['order_item_id']);
                            $errors++;
                        }
                    }

                    $this->log(Zend_Log::INFO, sprintf("Sync finished. %d items synced.", $items_synced));
                    Mage::helper("klevu_search/config")->setLastOrderSyncRun();

                    if ($errors) {
                        //$this->notify(Mage::helper("klevu_search")->__("Order Sync failed to sync some of the order items. Please consult the logs for more details."));
                    } else {
                        // If a sync finished without errors, existing notifications no longer apply
                        $this->deleteNotifications();
                    }
                }
            }
        } catch(Exception $e) {
            // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
            Mage::helper('klevu_search')->log(Zend_Log::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
            throw $e;
        }
    }

    /**
     * Sync the given order item to Klevu. Returns true on successful sync and
     * the error message otherwise.
     *
     * @param Mage_Sales_Model_Order_Item $item
     *
     * @return bool|string
     */
    protected function sync($item,$sess_id,$ip_address,$order_date,$order_email) {
        if (!$this->getApiKey($item->getStoreId())) {
            return "Klevu Search is not configured for this store.";
        }

        $parent = null;
        if ($item->getParentItemId()) {
            $parent = Mage::getModel("sales/order_item")->load($item->getParentItemId());
        }

        $response = Mage::getModel("klevu_search/api_action_producttracking")
            ->setStore(Mage::app()->getStore($item->getStoreId()))
            ->execute(array(
            "klevu_apiKey"    => $this->getApiKey($item->getStoreId()),
            "klevu_type"      => "checkout",
            "klevu_productId" => Mage::helper("klevu_search")->getKlevuProductId($item->getProductId(), ($parent) ? $parent->getProductId() : 0),
            "klevu_unit"      => $item->getQtyOrdered() ? $item->getQtyOrdered() : ($parent ? $parent->getQtyOrdered() : null),
            "klevu_salePrice" => $item->getPriceInclTax() ? $item->getPriceInclTax() : ($parent ? $parent->getPriceInclTax() : null),
            "klevu_currency"  => $this->getStoreCurrency($item->getStoreId()),
            "klevu_shopperIP" => $ip_address,
			"klevu_sessionId" => $sess_id,
			"klevu_orderDate" => $order_date,
			"klevu_emailId" => $order_email,
			"klevu_storeTimezone" => Mage::helper("klevu_search")->getStoreTimeZone($item->getStoreId()),
			"klevu_clientIp" => $this->getOrderIP($item->getOrderId())
        ));

        if ($response->isSuccessful()) {
            return true;
        } else {
            return $response->getMessage();
        }
    }

    /**
     * Check if Order Sync is enabled for the given store.
     *
     * @param $store_id
     *
     * @return bool
     */
    protected function isEnabled($store_id) {
        $is_enabled = $this->getData("is_enabled");
        if (!is_array($is_enabled)) {
            $is_enabled = array();
        }

        if (!isset($is_enabled[$store_id])) {
            $is_enabled[$store_id] = Mage::helper("klevu_search/config")->isOrderSyncEnabled($store_id);
            $this->setData("is_enabled", $is_enabled);
        }

        return $is_enabled[$store_id];
    }

    /**
     * Return the JS API key for the given store.
     *
     * @param $store_id
     *
     * @return string|null
     */
    protected function getApiKey($store_id) {
        $api_keys = $this->getData("api_keys");
        if (!is_array($api_keys)) {
            $api_keys = array();
        }

        if (!isset($api_keys[$store_id])) {
            $api_keys[$store_id] = Mage::helper("klevu_search/config")->getJsApiKey($store_id);
            $this->setData("api_keys", $api_keys);
        }

        return $api_keys[$store_id];
    }

    /**
     * Get the currency code for the given store.
     *
     * @param $store_id
     *
     * @return string
     */
    protected function getStoreCurrency($store_id) {
        $currencies = $this->getData("currencies");
        if (!is_array($currencies)) {
            $currencies = array();
        }

        if (!isset($currencies[$store_id])) {
            $currencies[$store_id] = Mage::app()->getStore($store_id)->getDefaultCurrencyCode();
            $this->setData("currencies", $currencies);
        }

        return $currencies[$store_id];
    }

    /**
     * Return the customer IP for the given order.
     *
     * @param $order_id
     *
     * @return string
     */
    protected function getOrderIP($order_id) {
        $order_ips = $this->getData("order_ips");
        if (!is_array($order_ips)) {
            $order_ips = array();
        }

        if (!isset($order_ips[$order_id])) {
            $order_ips[$order_id] = $this->getConnection()->fetchOne(
                $this->getConnection()
                    ->select()
                    ->from(array("order" => $this->getTableName("sales/order")), "remote_ip")
                    ->where("order.entity_id = ?", $order_id)
            );
            $this->setData("order_ips", $order_ips);
        }

        return $order_ips[$order_id];
    }

    /**
     * Return a select statement for getting all items in the sync queue.
     *
     * @return Zend_Db_Select
     */
    protected function getSyncQueueSelect() {
        return $this->getConnection()
            ->select()
            ->from($this->getTableName("klevu_search/order_sync"));
    }

    /**
     * Add the given order item IDs to the sync queue.
     *
     * @param $order_item_ids
     *
     * @return int
     */
    protected function addItemsToQueue($order_item_ids) {
        if (!is_array($order_item_ids)) {
            $order_item_ids = array($order_item_ids);
        }

        return $this->getConnection()->insertArray(
            $this->getTableName("klevu_search/order_sync"),
            array("order_item_id","klevu_session_id","ip_address","date","email"),
            $order_item_ids
        );
    }

    /**
     * Remove the given item from the sync queue.
     *
     * @param $order_item_id
     *
     * @return bool
     */
    protected function removeItemFromQueue($order_item_id) {
        return $this->getConnection()->delete(
            $this->getTableName("klevu_search/order_sync"),
            array("order_item_id" => $order_item_id)
        ) === 1;
    }

    /**
     * Create an Adminhtml notification for Order Sync, overwriting
     * any existing ones.
     *
     * @param $message
     *
     * @return $this
     */
    protected function notify($message) {
        $notification = Mage::getResourceModel("klevu_search/notification_collection")
            ->addFieldToFilter("type", array("eq" => static::NOTIFICATION_TYPE))
            ->getFirstItem();

        $notification->addData(array(
            "type"    => static::NOTIFICATION_TYPE,
            "date"    => Mage::getModel("core/date")->timestamp(),
            "message" => $message
        ));

        $notification->save();

        return $this;
    }

    /**
     * Delete Adminhtml notifications for Order Sync.
     *
     * @return $this
     */
    protected function deleteNotifications() {
        $this->getConnection()->delete(
            $this->getTableName('klevu_search/notification'),
            array("type" => static::NOTIFICATION_TYPE)
        );

        return $this;
    }
}

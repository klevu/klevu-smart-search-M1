<?php

class Klevu_Search_Adminhtml_Klevu_SearchController extends Mage_Adminhtml_Controller_Action {

    /* Sync data based on sync options selected */
    public function sync_allAction() {
        $store = $this->getRequest()->getParam("store");
        if ($store !== null) {
            try {
                $store = Mage::app()->getStore($store);
            } catch (Mage_Core_Model_Store_Exception $e) {
                Mage::getSingleton("adminhtml/session")->addError($this->__("Selected store could not be found!"));
                return $this->_redirectReferer("adminhtml/dashboard");
            }
        }

        if (Mage::helper('klevu_search/config')->isProductSyncEnabled()) {
            
            if(Mage::helper('klevu_search/config')->getSyncOptionsFlag() == "2") {
                if(Mage::helper("klevu_search/config")->isExternalCronEnabled()) {
                    Mage::getModel('klevu_search/product_sync')
                    ->markAllProductsForUpdate($store)
                    ->schedule();
                } else {
                     Mage::getModel('klevu_search/product_sync')
                    ->markAllProductsForUpdate($store);
                }

                if ($store) {
                    Mage::helper("klevu_search")->log(Zend_Log::INFO, sprintf("Product Sync scheduled to re-sync ALL products in %s (%s).",
                        $store->getWebsite()->getName(),
                        $store->getName()
                    ));

                    Mage::getSingleton("adminhtml/session")->addSuccess($this->__("Klevu Search Product Sync scheduled to be run on the next cron run for ALL products in %s (%s).",
                        $store->getWebsite()->getName(),
                        $store->getName()
                    ));
                } else {
                    Mage::helper("klevu_search")->log(Zend_Log::INFO, "Product Sync scheduled to re-sync ALL products.");

                    Mage::getSingleton('adminhtml/session')->addSuccess($this->__("Klevu Search Sync scheduled to be run on the next cron run for ALL products."));
                }
            } else {
                $this->syncWithoutCron();
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError($this->__("Klevu Search Product Sync is disabled."));
        }
        
        Mage::dispatchEvent('sync_all_external_data', array(
            'store' => $store
        ));

        return $this->_redirectReferer("adminhtml/dashboard");
    }
    
    /* Run the product sync externally */
    public function manual_syncAction() {
        Mage::getModel("klevu_search/product_sync")->runManually();
        /* Use event For other content sync */
        Mage::dispatchEvent('content_data_to_sync', array());
        Mage::getSingleton('klevu_search/session')->unsFirstSync();
        $storeId = Mage_Core_Model_App::ADMIN_STORE_ID;
        Mage::app()->setCurrentStore($storeId);
        return $this->_redirectReferer("adminhtml/dashboard");
    }
    
    /* Run the product sync */ 
    public function syncWithoutCron() {
        try {
            Mage::getModel("klevu_search/product_sync")->run();
            /* Use event For other content sync */
            Mage::dispatchEvent('content_data_to_sync', array());
			$memoryMessage = Mage::getSingleton('core/session')->getMemoryMessage();
			$failedMessage = Mage::getSingleton('core/session')->getKlevuFailedFlag();
		
			if(!empty($memoryMessage)) {
				$failedMessage = Mage::getSingleton('core/session')->getKlevuFailedFlag();
				if(!empty($failedMessage) && $failedMessage == 1) {
					$message = $this->__("Product sync failed.Please consult klevu_search.log file for more information.");
				} else {
					$message = $this->__("Data updates have been sent to Klevu.").$memoryMessage;
					Mage::getSingleton('core/session')->setMemoryMessage("");
				}
			} else {
				if(!empty($failedMessage) && $failedMessage == 1) {
					$message = $this->__("Product sync failed.Please consult klevu_search.log file for more information.");
				} else {
					$message = $this->__("Data updates have been sent to Klevu.");
				}
			}
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
			Mage::getSingleton('core/session')->setKlevuFailedFlag(1);
			
        } catch (Mage_Core_Model_Store_Exception $e) {
            Mage::logException($e);
        }
        $storeId = Mage_Core_Model_App::ADMIN_STORE_ID;
        Mage::app()->setCurrentStore($storeId);
        return $this->_redirectReferer("adminhtml/dashboard");
    }
    
    /* save sync options using Ajax */
    public function save_sync_options_configAction() {
        $sync_options = $this->getRequest()->getParam("sync_options");
        Mage::helper('klevu_search/config')->saveSyncOptions($sync_options);
    }
    
    public function trigger_options_configAction() {
        $triggeroptions = $this->getRequest()->getParam("triggeroptions");
        Mage::helper('klevu_search/config')->saveTrigger($triggeroptions);
    }
    /* clear the cron entry */
    public function clear_klevu_cronAction() {
        Mage::getModel("klevu_search/product_sync")->clearKlevuCron();
        Mage::getSingleton('adminhtml/session')->addSuccess($this->__("Running Klevu product Sync entry cleared from cron_schedule table."));
        return $this->_redirectReferer("adminhtml/dashboard");
    }

    public function triggerAction() 
    {
        $trigger = new Klevu_Trigger();
        $resource = Mage::getSingleton('core/resource');
        $write = $resource->getConnection('core_write');
        $dbname = (string)Mage::getConfig()->getNode('global/resources/default_setup/connection/dbname');
		$catalog_product_index_price = $resource->getTableName("catalog_product_index_price");
		$klevu_product_sync = $resource->getTableName("klevu_search/product_sync");
		$catalogrule_product_price = $resource->getTableName("catalogrule_product_price");
		$cataloginventory_stock_status = $resource->getTableName("cataloginventory_stock_status");
        try
        {
            if(Mage::helper('klevu_search/config')->getTriggerOptionsFlag() == "1") 
            {
                $write->query("DROP TRIGGER IF EXISTS Update_KlevuProductSync_For_CPIP;");
                $write->query("DROP TRIGGER IF EXISTS Update_KlevuProductSync_For_LSA;");
                $write->query("DROP TRIGGER IF EXISTS Update_KlevuProductSync_For_CPP;");
                // Set time SQL_TIME_BEFORE / SQL_TIME_AFTER
                $trigger->setTime($trigger::SQL_TIME_AFTER);
                $trigger->setName("Update_KlevuProductSync_For_CPIP");
                // Set time SQL_EVENT_INSERT / SQL_EVENT_UPDATE / SQL_EVENT_DELETE
                $trigger->setEvent($trigger::SQL_EVENT_UPDATE);

                // Set target table name
                $trigger->setTarget($catalog_product_index_price);

                // Set Body
                $trigger->setBody("IF NEW.price <> OLD.price || NEW.final_price <> OLD.final_price THEN
                IF (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '".$dbname."' AND  table_name = '{$klevu_product_sync}') <> 0 THEN
                UPDATE {$klevu_product_sync}
                SET last_synced_at = '0000-00-00 00:00:00'
                WHERE product_id = NEW.entity_id;
                END IF ;
                END IF ;");

                // Assemble query, returns direct SQL for trigger
                $triggerCreateFinalPriceQuery = $trigger->assemble();
                // Adapter initiates query
                $write->query($triggerCreateFinalPriceQuery);
                //reset previous query for executing new query
                $trigger->reset();
                // Set time SQL_TIME_BEFORE / SQL_TIME_AFTER
                $trigger->setTime($trigger::SQL_TIME_AFTER);
                $trigger->setName("Update_KlevuProductSync_For_LSA");
                // Set time SQL_EVENT_INSERT / SQL_EVENT_UPDATE / SQL_EVENT_DELETE
                $trigger->setEvent($trigger::SQL_EVENT_UPDATE);

                // Set target table name
                $trigger->setTarget($cataloginventory_stock_status);

                // Set Body
                $trigger->setBody("IF NEW.stock_status <> OLD.stock_status THEN
                IF (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '".$dbname."' AND table_name = '{$klevu_product_sync}') <> 0 THEN
                UPDATE {$klevu_product_sync}
                SET last_synced_at = '0000-00-00 00:00:00'
                WHERE product_id = NEW.product_id;
                END IF ;
                END IF ;");

                // Assemble query, returns direct SQL for trigger
                $triggerCreateStockQuery = $trigger->assemble();
                // Adapter initiates query
                $write->query($triggerCreateStockQuery);

                //reset previous query for executing new query
                $trigger->reset();
                // Set time SQL_TIME_BEFORE / SQL_TIME_AFTER
				if(Mage::getEdition() == "Enterprise") {
                    $trigger->setTime($trigger::SQL_TIME_BEFORE);
				} else {
					$trigger->setTime($trigger::SQL_TIME_AFTER);
				}
                $trigger->setName("Update_KlevuProductSync_For_CPP");
                // Set time SQL_EVENT_INSERT / SQL_EVENT_UPDATE / SQL_EVENT_DELETE
                $trigger->setEvent($trigger::SQL_EVENT_UPDATE);

                // Set target table name
                $trigger->setTarget($catalogrule_product_price);

                // Set Body
                $trigger->setBody("IF NEW.rule_price <> OLD.rule_price THEN
                IF (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '".$dbname."' AND table_name = '{$klevu_product_sync}') <> 0 THEN
                UPDATE {$klevu_product_sync}
                SET last_synced_at = '0000-00-00 00:00:00'
                WHERE product_id = NEW.product_id;
                END IF ;
                END IF ;");

                // Assemble query, returns direct SQL for trigger
                $triggerCreateRulePriceQuery = $trigger->assemble();
                // Adapter initiates query
                $write->query($triggerCreateRulePriceQuery);
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__("Trigger is activated."));
                return $this->_redirectReferer("adminhtml/dashboard");
            }
            else
            {
                $write->query("DROP TRIGGER IF EXISTS Update_KlevuProductSync_For_CPIP;");
                $write->query("DROP TRIGGER IF EXISTS Update_KlevuProductSync_For_LSA;");
                $write->query("DROP TRIGGER IF EXISTS Update_KlevuProductSync_For_CPP;");
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__("Trigger is deactivated."));
                return $this->_redirectReferer("adminhtml/dashboard");
            }
        } catch (Mage_Core_Model_Store_Exception $e) {
            Mage::logException($e);
        }
    }
    
    protected function _isAllowed()
    {
        return true;
    }
}

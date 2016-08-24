<?php
class Klevu_Content_Model_Observer extends Varien_Object 
{
    /* Re-syn all content and schedule cron for store */ 
    public function syncAllContent(Varien_Event_Observer $observer) {
        $store =  $observer->getStore();
        if(!empty($store)) {
            if (Mage::helper("content")->isCmsSyncEnabled($store->getId())) {
				if(Mage::helper("klevu_search/config")->isExternalCronEnabled()) {
                    Mage::getModel('content/content')->schedule();
				}
            } else {
                Mage::getSingleton('adminhtml/session')->addError(sprintf("Klevu Search Content Sync is disabled for %s (%s).", $store->getWebsite()->getName() , $store->getName()));
            }
        } else {
            $stores = Mage::app()->getStores();
            foreach($stores as $store) {
                if (!Mage::helper("content")->isCmsSyncEnabled($store->getId())) {
                    Mage::getSingleton('adminhtml/session')->addError(sprintf("Klevu Search Content Sync is disabled for %s (%s).", $store->getWebsite()->getName() , $store->getName()));
                    continue;
                } else {
					if(Mage::helper("klevu_search/config")->isExternalCronEnabled()) {
                        Mage::getModel('content/content')->schedule();
                    }						
                }
            }
        
        }
    }
    
    
    /**
     * Run Other content based on event call.
     *
     * @param Varien_Event_Observer $observer
     */
    public function syncOtherContent(Varien_Event_Observer $observer) {
        Mage::getModel("content/content")->run();
    }

    /**
     * Run Other content based on event call.
     *
     * @param Varien_Event_Observer $observer
     */
    public function scheduleOtherContent(Varien_Event_Observer $observer) {
        Mage::getModel("content/content")->schedule();
    }
    
    /**
     * Save excluded cms pages for store.
     *
     * @param Varien_Event_Observer $observer
     */
    public function saveExcludedCmsPages(Varien_Event_Observer $observer) {
        Mage::helper("content")->setCmsPageMap($observer->getExcludedPages(),$observer->getStore());
    }
    
}
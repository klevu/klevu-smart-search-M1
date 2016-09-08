<?php
class Klevu_Boosting_Model_Observer extends Varien_Object 
{
   
    /* update matching product ids */
    public function updateMatchingids(Varien_Event_Observer $observer) {
        $obj = $observer->getEvent()->getObject();
		$matchIds = $obj->getMatchingProductIds();
        $matchingids = ",".implode(",",$matchIds).",";
        Mage::getResourceModel("boosting/boost")->updateMatchingIds($matchingids,$obj->getId());
		if(!empty($matchIds)) {
		    Mage::getModel("klevu_search/product_sync")->updateSpecificProductIds($matchIds);
		}
    }
	
	/* update matching product ids */
    public function getMatchingidsDeleteBefore(Varien_Event_Observer $observer) {
        $obj = $observer->getEvent()->getObject();
        $deletdData = Mage::getResourceModel("boosting/boost")->getDeletedMatchingIds($obj->getId());
		if(!empty($deletdData)) {
			if(is_array($deletdData)){
				$deletdIds = explode(",",$deletdData[0]['matchingids']);
			}
			$updateIds = array_filter($deletdIds);
			if(!empty($updateIds)) {
		        Mage::getModel("klevu_search/product_sync")->updateSpecificProductIds($updateIds);
			}
		}
    }
	
   /* update previous matching product ids to restore the score */
    public function updatePreviousMatchingids(Varien_Event_Observer $observer) {
		$obj = $observer->getEvent()->getObject();
		$model = Mage::getModel('boosting/boost')->load($obj->getId());
		$matchIds = $model->getMatchingProductIds();
		if(!empty($matchIds)) {
		    Mage::getModel("klevu_search/product_sync")->updateSpecificProductIds($matchIds);
		}
    }
    
    /* update matching product ids */
    public function addBoostingAttribute(Varien_Event_Observer $observer) {
            $product = $observer->getEvent()->getProduct();
            $parent = $observer->getEvent()->getParent();
            $store = $observer->getEvent()->getStore();
            $boosting_settings = Mage::helper('klevu_search/config')->getBoostingAttribute($store);
            if($boosting_settings == "use_boosting_rule") {
                $boostarray = Mage::helper('boosting')->getProdcutsBoostingValues();
                if(!empty($boostarray)) {
                    if($parent) {
                            $product["boostingAttribute"] =  Mage::helper('boosting')->findMaxBoostValueForProduct($product['parent_id'],$boostarray);
                    } else if($product['product_id']){
                            $product["boostingAttribute"] =  Mage::helper('boosting')->findMaxBoostValueForProduct($product['product_id'],$boostarray);
                    }
                    $observer->getEvent()->setProduct($product);
                    return $this;
                }
            }
    }
    
    /* apply boosting rule for products */
    public function applyALlBoostingRule(Varien_Event_Observer $observer) {
        try {
            $collectionData = Mage::getModel("boosting/boost")->getCollection()->addFieldToFilter('status', '1')->addFieldToSelect('id');
            foreach($collectionData->getData() as $key => $value){
                $boostModel = Mage::getModel('boosting/boost')->load($value['id']);
                $matchingids = ",".implode(",",$boostModel->getMatchingProductIds()).",";
                Mage::getResourceModel("boosting/boost")->updateMatchingIds($matchingids,$value['id']);
            }
        } catch (Exception $e) {
            // Catch the exception that was thrown, log it.
            Mage::helper('klevu_search')->log(Zend_Log::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
        }
    }
 
}
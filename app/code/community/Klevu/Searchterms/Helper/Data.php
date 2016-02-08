<?php
class Klevu_Searchterms_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_POPULARSEARCH_ENABLED = "klevu_search/popular_search_term/enabledpopulartermfront";
    
    /**
     * Check if the add to cart is enabled in the system configuration for the current store.
     *
     * @param $store_id
     *
     * @return bool
     */
    public function isPoplularSearchPageEnabled($store_id = null) {
        return Mage::getStoreConfigFlag(static::XML_PATH_POPULARSEARCH_ENABLED, $store_id);
    }

}
	 
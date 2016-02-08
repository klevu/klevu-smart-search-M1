<?php
class Klevu_Addtocart_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_ADDTOCART_ENABLED = "klevu_search/add_to_cart/enabledaddtocartfront";
    
    /**
     * Check if the add to cart is enabled in the system configuration for the current store.
     *
     * @param $store_id
     *
     * @return bool
     */
    public function isAddtocartEnabled($store_id = null) {
        return Mage::getStoreConfigFlag(static::XML_PATH_ADDTOCART_ENABLED, $store_id);
    }
}
	 
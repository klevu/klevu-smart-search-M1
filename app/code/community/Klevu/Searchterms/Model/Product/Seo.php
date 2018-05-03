<?php

/**
 * Class Klevu_Searchterms_Model_Product_Seo
 *
 */
class Klevu_Searchterms_Model_Product_Seo
{

    /**
     * Return the JS API key for the given store.
     *
     * @param $store_id
     *
     * @return string|null
     */
    public function getApiKey($store_id) 
    {
        $api_keys = array();
        if (!isset($api_keys[$store_id])) {
            $api_keys[$store_id] = Mage::helper("klevu_search/config")->getJsApiKey($store_id);
        }

        return $api_keys[$store_id];
    }
    
     // Get n popular search terms
    public function getSearchTerms()
    {   
        $param =  array( "klevuApiKey" => $this->getApiKey(Mage::app()->getStore()->getStoreId()),
                         "topN"=> 1000);
        $response = Mage::getModel("klevu_searchterms/api_action_popularterms")
            ->setStore(Mage::app()->getStore())
            ->execute($param);
        $populersearch = $response->getData();
        if(!empty($populersearch)) {
            return $populersearch['term'];
        }
    }

    // Get n popular products with fromdate and todate
    public function getPopularProducts()
    {
        $param =  array( "klevuApiKey" => $this->getApiKey(Mage::app()->getStore()->getStoreId()));
        $response = Mage::getModel("klevu_searchterms/api_action_popularterms")
            ->setStore(Mage::app()->getStore())
            ->execute($param);
        $popularproducts = $response->getData();
        if(!empty($popularproducts)) {
            return $popularproducts['product'];
        }
    }
}

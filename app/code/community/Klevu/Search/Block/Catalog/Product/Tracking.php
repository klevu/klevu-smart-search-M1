<?php

class Klevu_Search_Block_Catalog_Product_Tracking extends Mage_Core_Block_Template
{

    /**
     * JSON of required tracking parameter for Klevu Product Click Tracking, based on current product
     * @return string
     * @throws Exception
     */
    public function getJsonTrackingData() 
    {
        // Get the product
        $product = Mage::registry('current_product');
        $api_key = Mage::helper('klevu_search/config')->getJsApiKey();

            $product = array(
                'klevu_apiKey' => $api_key,
                'klevu_term'   => '',
                'klevu_type'   => 'clicked',
                'klevu_productId' => $product->getId(),
                'klevu_productName' => $product->getName(),
                'klevu_productUrl' => $product->getProductUrl(),
                'klevu_sessionId' => session_id(),
                'Klevu_typeOfRecord' => 'KLEVU_PRODUCT'
            );

        return json_encode($product);
    }
}

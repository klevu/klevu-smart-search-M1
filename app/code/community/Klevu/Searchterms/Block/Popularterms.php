<?php

class Klevu_Searchterms_Block_Catalog_Product_Popularterms extends Mage_Core_Block_Template
{
    /**
     * Get the popular search terms
     * @return string
     * @throws Exception
     */
    public function getPopularterms() 
    {
        // Get the product
        return Mage::getModel("klevu_searchterms/product_seo")->getSearchTerms();
    }
}

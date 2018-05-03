<?php

/**
 * Class Klevu_Search_Model_Observer
 *
 */
class Klevu_Searchterms_Model_Observer
{

    // Get n popular products with fromdate and todate
    public function applySearchtermsPageModelRewrites(Varien_Event_Observer $observer)
    {
        $config = Mage::helper('klevu_searchterms');
        if ($config->isPoplularSearchPageEnabled()) {
            Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('popular-search-terms'));
        }
      
    }
}

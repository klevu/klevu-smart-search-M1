<?php

class Klevu_Search_Model_System_Config_Source_Taxoptions
{

    const YES    = 1;
    const NO     = 0;
    const NEVER  = 2;


    public function toOptionArray() 
    {
        $helper = Mage::helper("klevu_search");

        return array(
            array('value' => static::NEVER, 'label' => $helper->__("Do not add tax in price as catalog prices entered by admin already include tax")),
            array('value' => static::NO, 'label' => $helper->__("Do not add tax in price as product prices are displayed without tax")),
            array('value' => static::YES, 'label' => $helper->__("Add relevant tax in price as product prices need to be displayed with tax"))
        );
    }
}

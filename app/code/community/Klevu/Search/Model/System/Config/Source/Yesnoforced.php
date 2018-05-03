<?php

class Klevu_Search_Model_System_Config_Source_Yesnoforced
{

    const YES    = 1;
    const NO     = 0;
    const FORCED = 2;

    public function toOptionArray() 
    {
        $helper = Mage::helper("klevu_search");

        return array(
            array('value' => static::YES, 'label' => $helper->__("Yes")),
            array('value' => static::NO, 'label' => $helper->__("No"))
           // array('value' => static::FORCED, 'label' => $helper->__("Forced"))
        );
    }
}

<?php

class Klevu_Search_Model_System_Config_Source_Additional_Attributes {

    public function toOptionArray() {
        $helper = Mage::helper('klevu_search');

        return array(
            array('value' => "brand", 'label' => $helper->__("Brand")),
            array('value' => "model", 'label' => $helper->__("Model")),
            array('value' => "color", 'label' => $helper->__("Color")),
            array('value' => "size" , 'label' => $helper->__("Size"))
        );
    }
}

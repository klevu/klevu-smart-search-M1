<?php

class Klevu_Search_Block_Adminhtml_Form_Field_Automatic_Attribute_Mappings extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract {

    /**
     * Check if columns are defined, set template
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('klevu/search/form/field/array_readonly.phtml');
    }

    protected function _prepareToRender() {
        $this->addColumn("klevu_attribute", array(
            'label'    => Mage::helper('klevu_search')->__("Klevu Attribute")
        ));
        $this->addColumn("magento_attribute", array(
            'label'    => Mage::helper('klevu_search')->__("Magento Attribute")
        ));
    }
}

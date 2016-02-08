<?php
class Klevu_Boosting_Block_Adminhtml_Boost extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()

    {
        $this->_controller = "adminhtml_boost";
        $this->_blockGroup = "boosting";
        $this->_headerText = Mage::helper("boosting")->__("Klevu Product Boosting Manager");
        $this->_addButtonLabel = Mage::helper("boosting")->__("Add New Rule");
        parent::__construct();
    }
}
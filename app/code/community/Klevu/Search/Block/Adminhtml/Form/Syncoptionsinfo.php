<?php

class Klevu_Search_Block_Adminhtml_Form_Syncoptionsinfo extends Mage_Adminhtml_Block_System_Config_Form_Field {
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        if($this->getSyncOptionsSelected() ==1) {
            $mode = Mage::helper("klevu_search")->__("Updates only (syncs data immediately)");
        } else {
            $mode = Mage::helper("klevu_search")->__("All data (syncs data on CRON execution");
        }
        return $mode;
    }

    public function render(Varien_Data_Form_Element_Abstract $element) {
        $this->setData('scope', $element->getScope());

        // Remove the inheritance checkbox
        $element
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();

        return parent::render($element);
    }



    public function getSyncOptionsSelected() {
        return  Mage::helper('klevu_search/config')->getSyncOptionsFlag();
    }

}

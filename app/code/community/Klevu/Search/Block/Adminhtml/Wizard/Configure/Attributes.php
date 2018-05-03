<?php

class Klevu_Search_Block_Adminhtml_Wizard_Configure_Attributes extends Mage_Adminhtml_Block_Template
{

    /**
     * Return the submit URL for the store configuration form.
     *
     * @return string
     */
    protected function getFormActionUrl() 
    {
        return $this->getUrl("adminhtml/klevu_search_wizard/configure_attributes_post");
    }

    protected function getAttributeMappingsHtml() 
    {
        $element = new Varien_Data_Form_Element_Text(
            array(
            "name" => "attributes",
            "label" => $this->__("Additional Attributes"),
            "comment" => $this->__('Here you can set optional product attributes sent to Klevu by mapping them to your Magento attributes. If you specify multiple mappings for the same Klevu attribute, only the first mapping found on the product sent will be used, except for the "Other" attribute where all existing mappings are used.'),
            "tooltip" => "",
            "hint"    => "",
            "value"   => Mage::helper("klevu_search/config")->getAdditionalAttributesMap($this->getStore()),
            "inherit" => false,
            "class"   => "",
            "can_use_default_value" => false,
            "can_use_website_value" => false
            )
        );
        $element->setForm(new Varien_Data_Form());

        /** @var Klevu_Search_Block_Adminhtml_Form_Field_Attribute_Mappings $renderer */
        $renderer = Mage::getBlockSingleton("klevu_search/adminhtml_form_field_attribute_mappings");
        $renderer->setTemplate("klevu/search/wizard/form/field/array.phtml");

        return $renderer->render($element);
    }

    /**
     * Return the Store model for the currently configured store.
     *
     * @return Mage_Core_Model_Store|null
     */
    protected function getStore() 
    {
        if (!$this->hasData('store')) {
            $store_code = Mage::getSingleton('klevu_search/session')->getConfiguredStoreCode();

            $this->setData('store', Mage::app()->getStore($store_code));
        }

        return $this->getData('store');
    }
}

<?php

class Klevu_Search_Block_Adminhtml_Form_Field_Attribute_Mappings extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

    protected $klevu_attribute_renderer;

    protected $magento_attribute_renderer;

    protected function _prepareToRender() 
    {
        $this->addColumn(
            "klevu_attribute", array(
            'label'    => Mage::helper('klevu_search')->__("Klevu Attribute"),
            'renderer' => $this->getKlevuAttributeRenderer()
            )
        );
        $this->addColumn(
            "magento_attribute", array(
            'label'    => Mage::helper('klevu_search')->__("Magento Attribute"),
            'renderer' => $this->getMagentoAttributeRenderer()
            )
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('klevu_search')->__("Add Mapping");
    }

    protected function _prepareArrayRow(Varien_Object $row) 
    {
        $row->setData(
            'option_extra_attr_' . $this->getKlevuAttributeRenderer()->calcOptionHash($row->getData('klevu_attribute')),
            'selected="selected"'
        );
        $row->setData(
            'option_extra_attr_' . $this->getMagentoAttributeRenderer()->calcOptionHash($row->getData('magento_attribute')),
            'selected="selected"'
        );
    }

    /**
     * Return a block to render the select element for Klevu Attribute.
     *
     * @return Klevu_Search_Block_Adminhtml_Form_Field_Html_Select
     */
    protected function getKlevuAttributeRenderer() 
    {
        if (!$this->klevu_attribute_renderer) {
            /** @var Mage_Core_Block_Html_Select $renderer */
            $renderer = $this->getLayout()->createBlock(
                'klevu_search/adminhtml_form_field_html_select', '', array(
                'is_render_to_js_template' => true
                )
            );
            $renderer->setOptions(Mage::getModel('klevu_search/system_config_source_additional_attributes')->toOptionArray());
            $renderer->setExtraParams('style="width:120px"');

            $this->klevu_attribute_renderer = $renderer;
        }

        return $this->klevu_attribute_renderer;
    }

    /**
     * Return a block to render the select element for Magento Attribute.
     *
     * @return Klevu_Search_Block_Adminhtml_Form_Field_Html_Select
     */
    protected function getMagentoAttributeRenderer() 
    {
        if (!$this->magento_attribute_renderer) {
            /** @var Mage_Core_Block_Html_Select $renderer */
            $renderer = $this->getLayout()->createBlock(
                'klevu_search/adminhtml_form_field_html_select', '', array(
                'is_render_to_js_template' => true
                )
            );
            $renderer->setOptions($this->getOptions());
            $renderer->setExtraParams('style="width:120px"');

            $this->magento_attribute_renderer = $renderer;
        }

        return $this->magento_attribute_renderer;
    }

    /**
     * Get the options from our product attribute source model, and filter out the search attributes.
     * @return array
     */
    protected function getOptions() 
    {
        $options_with_search_filters = Mage::getModel('klevu_search/system_config_source_product_attributes')->toOptionArray();
        $search_attributes_map = Mage::helper('klevu_search/config')->getAutomaticAttributesMap(Mage::app()->getRequest()->getParam('store'));
        $options = array();

        // Flatten the search_attributes_map
        $search_attributes = array();
        foreach($search_attributes_map as $attribute) {
            $search_attributes[] = $attribute['magento_attribute'];
        }

        // We only want options that are not in the search_attributes array.
        foreach($options_with_search_filters as $option) {
            if(!in_array($option['value'], $search_attributes)) {
                $options[] = $option;
            }
        }

        return $options;
    }
}

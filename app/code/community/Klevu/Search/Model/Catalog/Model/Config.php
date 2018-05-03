<?php
class Klevu_Search_Model_Catalog_Model_Config extends Mage_Catalog_Model_Config
{
   /**
     * Retrieve Attributes Used for Sort by as array
     * key = code, value = name
     *
     * @return array
     */
    public function getAttributeUsedForSortByArray()
    {
        if (!Mage::helper('klevu_search/config')->isExtensionConfigured() || !Mage::helper('klevu_search')->isCatalogSearch()) {
            $options = array(
            'position'  => Mage::helper('catalog')->__('Position')
            );
            foreach ($this->getAttributesUsedForSortBy() as $attribute) {
                /* @var $attribute Mage_Eav_Model_Entity_Attribute_Abstract */
                $options[$attribute->getAttributeCode()] = $attribute->getStoreLabel();
            }
        }else {
            $options = array(
            'position'  => Mage::helper('catalog')->__('Position'),
            'name' => Mage::helper('catalog')->__('Name'),
            'price' => Mage::helper('catalog')->__('Price'), 
            );
        }

        return $options;
    }
}

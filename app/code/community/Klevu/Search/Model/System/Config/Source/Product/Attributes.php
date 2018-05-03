<?php

class Klevu_Search_Model_System_Config_Source_Product_Attributes
{

    public function toOptionArray() 
    {
        $options = array();

        $attributes = $this->getAttributeCollection();
        foreach ($attributes as $attribute) {
            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
            $options[] = array(
                'value' => $attribute->getAttributeCode(),
                'label' => $attribute->getAttributeCode()
            );
        }

        return $options;
    }

    /**
     * Return a product attribute collection.
     *
     * @return Mage_Catalog_Model_Resource_Product_Attribute_Collection
     */
    protected function getAttributeCollection() 
    {
        /** @var Mage_Catalog_Model_Resource_Product_Attribute_Collection $collection */
        $collection = Mage::getResourceModel('catalog/product_attribute_collection');
        // Filter out attributes mapped by default
        $attributes = Mage::helper('klevu_search/config')->getDefaultMappedAttributes();
        $collection->addFieldToFilter(
            'attribute_code', array(
            'nin' => array_unique($attributes['magento_attribute'])
            )
        );

        return $collection;
    }
}

<?php

class Klevu_Search_Model_System_Config_Source_Boosting_Attribute {

    /**
     * Fetch all integer and decimal attributes and return in as options array.
     *
     * @return array
     */
    public function toOptionArray() {
        $attributes = $this->getAttributeCollection();
        $boost_option = array(
            'value' => null,
            'label' => ''
        );
        $options = array(
            array(
                'value' => null,
                'label' => '--- No Attribute Selected ---'
            ),
            $boost_option
        );
        foreach($attributes as $attribute) {
            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
            $options[] = array(
                'value' => $attribute->getAttributeCode(),
                'label' => $attribute->getAttributeCode()
            );
        }

        return $options;
    }

    /**
     * Get only integer,varchar and decimal attributes that are not used by Klevu yet.
     *
     * @return Mage_Catalog_Model_Resource_Product_Attribute_Collection
     */
    protected function getAttributeCollection() {
        /** @var Mage_Catalog_Model_Resource_Product_Attribute_Collection $collection */
        $collection = Mage::getResourceModel('catalog/product_attribute_collection');

        // We want integers,varchar and decimal attributes.
        $collection->addFieldToFilter(
            'backend_type',
            array(
                'in' => array(
                    'int',
                    'decimal',
                    'varchar'
                )
            )
        );

        $attributes = Mage::helper('klevu_search/config')->getDefaultMappedAttributes();

        // Exclude attributes used by default (only default int/decimal attributes)
        $collection->addFieldToFilter(
            'attribute_code',
            array(
                'nin' => array_unique($attributes['magento_attribute'])
            )
        );

        return $collection;
    }
}

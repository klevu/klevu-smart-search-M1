<?php

class Klevu_Boosting_Model_System_Config_Source_Boosting_Attribute extends Klevu_Search_Model_System_Config_Source_Boosting_Attribute
{

    /**
     * Fetch all integer and decimal attributes and return in as options array.
     *
     * @return array
     */
    public function toOptionArray() 
    {
        $attributes = $this->getAttributeCollection();
        $boost_option = array(
            'value' => null,
            'label' => ''
        );
        if(Mage::helper('core')->isModuleEnabled("Klevu_Boosting"))
        {
            $boost_option = array(
                'value' => 'use_boosting_rule',
                'label' => 'Apply Product Boosting Rules'
            );
        }

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

}

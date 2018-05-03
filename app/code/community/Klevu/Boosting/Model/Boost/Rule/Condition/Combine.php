<?php
class Klevu_Boosting_Model_Boost_Rule_Condition_Combine extends Mage_CatalogRule_Model_Rule_Condition_Combine
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('boosting/boost_rule_condition_combine');
    }
    
    public function getNewChildSelectOptions()
    {
        $productCondition = Mage::getModel('boosting/boost_rule_condition_product');
        $productAttributes = $productCondition->loadAttributeOptions()->getAttributeOption();
        $attributes = array();
        foreach ($productAttributes as $code=>$label) {
            $attributes[] = array('value'=>'boosting/boost_rule_condition_product|'.$code, 'label'=>$label);
        }

        //$conditions = parent::getNewChildSelectOptions();
        $conditions =array();
        $conditions = array_merge_recursive(
            $conditions, array(
            array('value'=>'boosting/boost_rule_condition_combine', 'label'=>Mage::helper('catalogrule')->__('Conditions Combination')),
            array('label'=>Mage::helper('catalogrule')->__('Product Attribute'), 'value'=>$attributes),
            )
        );
        return $conditions;
    }

    public function collectValidatedAttributes($productCollection)
    {
        foreach ($this->getConditions() as $condition) {
            $condition->collectValidatedAttributes($productCollection);
        }

        return $this;
    }
    


}
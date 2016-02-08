<?php
class Klevu_Boosting_Model_Boost extends Mage_CatalogRule_Model_Rule
{
    protected $_eventPrefix = 'boosting';
    protected $_eventObject = 'object';
    protected function _construct()
    {
        $this->_init("boosting/boost");
    }
    public function getConditionsInstance()

    {
        return Mage::getModel('boosting/boost_rule_condition_combine');
    }
    /**
     * Get array of product ids which are matched by rule
     *
     * @return array
     */
    public function getMatchingProductIds()
    {
        $rows = array();
        if (is_null($this->_productIds)) {
            $this->_productIds = array();
            $this->setCollectedAttributes(array());
            /** @var $productCollection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
            $productCollection = Mage::getResourceModel('catalog/product_collection');
            $this->getConditions()->collectValidatedAttributes($productCollection);
            Mage::getSingleton('core/resource_iterator')->walk($productCollection->getSelect() , array(
                array(
                    $this,
                    'callbackValidateProduct'
                )
            ) , array(
                'attributes' => $this->getCollectedAttributes() ,
                'product' => Mage::getModel('catalog/product') ,
            ));
        }
        if (version_compare(Mage::getVersion(), '1.7.0.2', '<=')===true) {
            return $this->_productIds;
        }
        foreach($this->_productIds as $key => $value) {
            if ($value[0] == 1) {
                $rows[] = $key;
            }
        }
        return $rows;
    }
}

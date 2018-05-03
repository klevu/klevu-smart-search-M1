<?php
class Klevu_Boosting_Model_Boost_Rule_Condition_Product extends Mage_CatalogRule_Model_Rule_Condition_Product
{
    
    /**
     * Validate product attribute value for condition
     *
     * @param Varien_Object $object
     * @return bool
     */
    public function validate(Varien_Object $object)
    {
        $attrCode = $this->getAttribute();
        if ('category_ids' == $attrCode) {
            return $this->validateAttribute($object->getAvailableInCategories());
        }

        if ('attribute_set_id' == $attrCode) {
            return $this->validateAttribute($object->getData($attrCode));
        }

        $oldAttrValue = $object->hasData($attrCode) ? $object->getData($attrCode) : null;
        $object->setData($attrCode, $this->_getAttributeValue($object));
        $result = $this->_validateProduct($object);
        $this->_restoreOldAttrValue($object, $oldAttrValue);

        return (bool)$result;
    }

    
    /**
     * Get attribute value
     *
     * @param Varien_Object $object
     * @return mixed
     */
    protected function _getAttributeValue($object)
    {
    
        $storeId = $object->getStoreId();
        $defaultStoreId = Mage_Core_Model_App::ADMIN_STORE_ID;
        $productValues  = isset($this->_entityAttributeValues[$object->getId()])
            ? $this->_entityAttributeValues[$object->getId()] : array($defaultStoreId => $object->getData($this->getAttribute()));
        $defaultValue = isset($productValues[$defaultStoreId]) ? $productValues[$defaultStoreId] : null;
        $value = isset($productValues[$storeId]) ? $productValues[$storeId] : $defaultValue;
        $value = $this->_prepareDatetimeValue($value, $object);
        $value = $this->_prepareMultiselectValue($value, $object);

        return $value;
    }
    
    /**
     * Prepare datetime attribute value
     *
     * @param mixed $value
     * @param Varien_Object $object
     * @return mixed
     */
    protected function _prepareDatetimeValue($value, $object)
    {
        $attribute = $object->getResource()->getAttribute($this->getAttribute());
        if ($attribute && $attribute->getBackendType() == 'datetime') {
            $value = date('Y-m-d', strtotime($value));
        }

        return $value;
    }

    /**
     * Prepare multiselect attribute value
     *
     * @param mixed $value
     * @param Varien_Object $object
     * @return mixed
     */
    protected function _prepareMultiselectValue($value, $object)
    {
        $attribute = $object->getResource()->getAttribute($this->getAttribute());
        if ($attribute && $attribute->getFrontendInput() == 'multiselect') {
            $value = strlen($value) ? explode(',', $value) : array();
        }

        return $value;
    }
    
    /**
     * Validate product
     *
     * @param Varien_Object $object
     * @return bool
     */
    protected function _validateProduct($object)
    {
        return Mage_Rule_Model_Condition_Abstract::validate($object);
    }
    
    /**
     * Restore old attribute value
     *
     * @param Varien_Object $object
     * @param mixed $oldAttrValue
     */
    protected function _restoreOldAttrValue($object, $oldAttrValue)
    {
        $attrCode = $this->getAttribute();
        if (is_null($oldAttrValue)) {
            $object->unsetData($attrCode);
        } else {
            $object->setData($attrCode, $oldAttrValue);
        }
    }
}

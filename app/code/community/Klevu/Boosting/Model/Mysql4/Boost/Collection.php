<?php
class Klevu_Boosting_Model_Mysql4_Boost_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init("boosting/boost");
    }
    public function addActiveFilter()
    {
        return $this->addFieldToFilter('is_active', 1);
    }
}

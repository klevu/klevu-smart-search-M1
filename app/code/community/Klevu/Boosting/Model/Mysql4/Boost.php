<?php
class Klevu_Boosting_Model_Mysql4_Boost extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("boosting/boost", "id");
    }
    public function updateMatchingIds($matchingids, $id)
    {
        $write = $this->_getWriteAdapter();
        $write->update(Mage::getSingleton('core/resource')->getTableName('boosting/boost') , array(
            "matchingids" => $matchingids
        ) , "id=" . $id);
    }
	
	public function getDeletedMatchingIds($id)
    {
        $adapter = $this->_getReadAdapter();
        $bind    = array('id'  => $id);
        $select  = $adapter->select()
            ->from(Mage::getSingleton('core/resource')->getTableName('boosting/boost'), array('matchingids'))
            ->where('id = :id');
        return $adapter->fetchAll($select, $bind);
    }
    
    // this function included for magento 1.5 and 1.6 only 
    public function updateRuleProductData(){
    
    }
}
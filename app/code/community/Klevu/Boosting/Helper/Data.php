<?php
class Klevu_Boosting_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Return the Boost value for the given product from klevu boosting rule module.
     *
     * @param array $product_id.
     *
     * @return int as boost value.
     */
    public function applyBoostRuleToProduct($id)
    {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $select = $connection->select()->from(Mage::getSingleton('core/resource')->getTableName('boosting/boost') , array(
            'MAX(boosting) AS boost_value'
        ))->where("matchingids", array(
            "finset" => $id
        ));
        $result = $connection->fetchCol($select);
        return $result[0];
    }
    /**
     * Return array of product ids for boosting rules.
     *
     * @return array.
     */
    public function getProdcutsBoostingValues()
    {
        $boostRules = Mage::getModel("boosting/boost")->getCollection()->addFieldToFilter('status', '1');
        if (count($boostRules) > 0) {
            foreach($boostRules as $obj) {
                $boostarr[$obj->getId() ][$obj->getBoosting() ] = $obj->getMatchingids();
            }
            return $boostarr;
        }
    }
    /**
     * Return array of product ids for boosting rules.
     *
     * @param int $product_id,$boostarray
     *
     * @return array.
     */
    public function findMaxBoostValueForProduct($id, $boostarray)
    {
        $product['curent_boost_value'] = 0;
        $product['prev_boost'] = 0;
        foreach($boostarray as $key => $value) {
            foreach($value as $boostkey => $boostvalue) {
                $find = "," . $id . ",";
                if (strpos($boostvalue, $find) !== false) {
                    if ($product['prev_boost'] < $boostkey) {
                        $product['curent_boost_value'] = $boostkey;
                    }
                    $product['prev_boost'] = $boostkey;
                }
            }
        }
        
        if($product['curent_boost_value'] > 0) {
            return $product['curent_boost_value'];
        } else {
            return;
        }
    }
 
}
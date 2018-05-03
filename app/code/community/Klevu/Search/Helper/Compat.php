<?php

class Klevu_Search_Helper_Compat extends Mage_Core_Helper_Abstract
{

    /**
     * Return a Select statement for retrieving URL rewrites for the given list of products.
     *
     * Uses the Product URL Rewrite Helper if available, falling back to building the query manually.
     *
     * @param array $product_ids
     * @param       $category_id
     * @param       $store_id
     *
     * @return Varien_Db_Select
     */
    public function getProductUrlRewriteSelect(array $product_ids, $category_id, $store_id) 
    {
        if (version_compare(Mage::getVersion(), '1.8.0', '>=')) {
            $factory_model_class = Mage::app()->getConfig()->getModelClassName("catalog/factory");
                if (class_exists($factory_model_class)) {
                    return Mage::getModel("catalog/factory")->getProductUrlRewriteHelper()
                ->getTableSelect($product_ids, $category_id, $store_id);
                }
        }

        $resource = Mage::getModel("core/resource");

        return $resource->getConnection("core_write")->select()
            ->from($resource->getTableName("core/url_rewrite"), array("product_id", "request_path"))
            ->where("store_id = ?", (int) $store_id)
            ->where("is_system = ?", 1)
            ->where("category_id = ? OR category_id IS NULL", (int) $category_id)
            ->where("product_id IN (?)", $product_ids)
            ->order("category_id " . Varien_Data_Collection::SORT_ORDER_DESC);
    }

    /**
     * Return the current date in internal format.
     *
     * @param bool $withoutTime day only flag
     *
     * @return string
     */
    public function now($withoutTime = false) 
    {
        if (method_exists("Varien_Date", "now")) {
            return Varien_Date::now($withoutTime);
        } else {
            $format = ($withoutTime) ? "Y-m-d" : "Y-m-d H:i:s";
            return date($format);
        }
    }
}

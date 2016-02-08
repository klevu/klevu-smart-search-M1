<?php

class Klevu_Search_Model_CatalogSearch_Resource_Layer_Filter_Attribute extends Mage_Catalog_Model_Resource_Eav_Mysql4_Layer_Filter_Attribute {

    /**
     * Stub method to prevent filters being applied. Klevu handles all filtering.
     *
     * @param Mage_Catalog_Model_Layer_Filter_Attribute $filter
     * @param int $value
     * @return $this|Mage_Catalog_Model_Resource_Layer_Filter_Attribute
     */
    public function applyFilterToCollection($filter, $value) {
        // If the Klevu module is not configured/enabled, run the parent method.
        if (!Mage::helper('klevu_search/config')->isExtensionConfigured() || !Mage::helper('klevu_search')->isCatalogSearch()) {
            parent::applyFilterToCollection($filter, $value);
        }

        return $this;
    }
}

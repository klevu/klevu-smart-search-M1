<?php
class Klevu_Content_Block_Index extends Mage_Core_Block_Template
{
    /**
     * Get the Klevu other content
     * @return array
     */
    public function getCmsContent()
    {
        $collection = Mage::helper("content")->getCmsData();
        return $collection;
    }
    /**
     * Return the Klevu other content filters
     * @return array
     */
    public function getContentFilters()

    {
        $filters = Mage::helper("content")->getKlevuFilters();
        return $filters;
    }
}
<?php

class Klevu_Search_Model_System_Config_Source_Syncoptions {

    const SYNC_PARTIALLY = 1;
    const SYNC_ALL = 2;

    public function toOptionArray() {
        $helper = Mage::helper("klevu_search");
        return array(
            array(
                'label' => $helper->__("Updates only (syncs data immediately)"),
                'value' => static::SYNC_PARTIALLY
            ),
            array(
                'label' => $helper->__("All data (syncs data on CRON execution)"),
                'value' => static::SYNC_ALL
            )
        );
    }
}

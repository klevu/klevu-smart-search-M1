<?php

class Klevu_Search_Model_System_Config_Source_Frequency {

    const CRON_HOURLY = "0 * * * *";
    const CRON_EVERY_3_HOURS = "0 */3 * * *";
    const CRON_EVERY_6_HOURS = "0 */6 * * *";
    const CRON_EVERY_12_HOURS = "0 */12 * * *";
    const CRON_DAILY = "0 3 * * *";

    public function toOptionArray() {
        $helper = Mage::helper("klevu_search");

        return array(
            array(
                'label' => $helper->__("Hourly"),
                'value' => static::CRON_HOURLY
            ),
            array(
                'label' => $helper->__("Every %s hours", 3),
                'value' => static::CRON_EVERY_3_HOURS
            ),
            array(
                'label' => $helper->__("Every %s hours", 6),
                'value' => static::CRON_EVERY_6_HOURS
            ),
            array(
                'label' => $helper->__("Every %s hours", 12),
                'value' => static::CRON_EVERY_12_HOURS
            ),
            array(
                'label' => $helper->__("Daily"),
                'value' => static::CRON_DAILY
            ),
        );
    }
}

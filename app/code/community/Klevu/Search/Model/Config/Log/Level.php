<?php

class Klevu_Search_Model_Config_Log_Level extends Mage_Core_Model_Config_Data {

    /**
     * Return the log level value. Return Zend_Log::WARN as default, if none set.
     *
     * @return int
     */
    public function getValue() {
        $value = $this->getData('value');

        return ($value != null) ? intval($value) : Zend_Log::WARN;
    }
}

<?php

class Klevu_Search_Model_System_Config_Source_Log_Level
{

    public function toOptionArray() 
    {
        $helper = Mage::helper('klevu_search');

        return array(
            array('value' => Zend_Log::EMERG,  'label' => $helper->__("Emergency")),
            array('value' => Zend_Log::ALERT,  'label' => $helper->__("Alert")),
            array('value' => Zend_Log::CRIT,   'label' => $helper->__("Critical")),
            array('value' => Zend_Log::ERR,    'label' => $helper->__("Error")),
            array('value' => Zend_Log::WARN,   'label' => $helper->__("Warning")),
            array('value' => Zend_Log::NOTICE, 'label' => $helper->__("Notice")),
            array('value' => Zend_Log::INFO,   'label' => $helper->__("Information")),
            array('value' => Zend_Log::DEBUG,  'label' => $helper->__("Debug"))
        );
    }
}

<?php

class Klevu_Search_Test_Model_Config_Log_Level extends EcomDev_PHPUnit_Test_Case
{

    /**
     * @test
     */
    public function testGetValue() 
    {
        $model = Mage::getModel('klevu_search/config_log_level');

        // Test the default value
        $this->assertEquals(Zend_Log::WARN, $model->getValue(), "getValue() returned an incorrect default value.");

        // Test a set value
        $model->setValue(Zend_Log::INFO);

        $this->assertEquals(Zend_Log::INFO, $model->getValue(), "getValue() didn't return the value set.");
    }
}

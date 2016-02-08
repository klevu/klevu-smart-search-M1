<?php

class Klevu_Search_Test_Config_Base extends EcomDev_PHPUnit_Test_Case_Config {

    /**
     * @test
     */
    public function testClassAlias() {
        $this->assertBlockAlias("klevu_search/test", "Klevu_Search_Block_Test");
        $this->assertHelperAlias("klevu_search/test", "Klevu_Search_Helper_Test");
        $this->assertModelAlias("klevu_search/test", "Klevu_Search_Model_Test");
    }
}

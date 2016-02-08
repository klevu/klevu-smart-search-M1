<?php

class Klevu_Search_Test_Helper_Compat extends EcomDev_PHPUnit_Test_Case {

    /** @var Klevu_Search_Helper_Compat $helper */
    protected $helper;

    protected function setUp() {
        parent::setUp();

        $this->helper = Mage::helper("klevu_search/compat");
    }

    /**
     * @test
     */
    public function testGetProductUrlRewriteSelect() {
        $this->assertInstanceOf("Varien_Db_Select", $this->helper->getProductUrlRewriteSelect(array(1), 0, 1));
    }
}

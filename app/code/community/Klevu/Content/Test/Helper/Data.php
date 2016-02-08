<?php

class Klevu_Content_Test_Helper_Data extends EcomDev_PHPUnit_Test_Case {

    /** @var Klevu_Content_Helper_Data $helper */
    protected $helper;

    protected function setUp() {
        parent::setUp();
        $this->helper = Mage::helper("content");
        $this->getConfig()->deleteConfig("klevu_search/product_sync/enabledcms");
    }
    
    protected function getConfig() {
        return Mage::app()->getConfig();
    }
    
    
    /**
     * @test
     * @loadFixture
     */
    public function testIsCmsEnabledEnabled() {
        $this->assertEquals(true, $this->helper->isCmsSyncEnabled());
    }

    /**
     * @test
     * @loadFixture
     */
    public function testIsCmsEnabledDisabled() {
        $this->assertEquals(false, $this->helper->isCmsSyncEnabled());
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function testIsCmsSyncEnabled($input, $output) {
        $this->assertEquals($output, $this->helper->isCmsSyncEnabled($input));
    }
    
    /**
     * @test
     * @dataProvider dataProvider
     */
    /*public function testGetExcludedCmsPages($input,$storeId)
    {
        $this->assertEquals($input,$this->helper->getExcludedCmsPages($this->getStore($storeId)));
    }*/
  
}

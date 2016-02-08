<?php

class Klevu_Search_Test_Helper_Data extends EcomDev_PHPUnit_Test_Case {

    /** @var Klevu_Search_Helper_Data $helper */
    protected $helper;

    protected function setUp() {
        parent::setUp();

        $this->helper = Mage::helper("klevu_search");
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function testGetLanguageFromLocale($input, $output) {
        $this->assertEquals($output, $this->helper->getLanguageFromLocale($output));
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function testIsProductionDomain($domain, $result) {
        $this->assertEquals(
            $result,
            $this->helper->isProductionDomain($domain),
            sprintf("Domain %s should %s a production domain.", $domain, ($result ? "be" : "NOT be"))
        );
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function testBytesToHumanReadable($input, $output) {
        $this->assertEquals($output, $this->helper->bytesToHumanReadable($input));
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function testHumanReadableToBytes($input, $output) {
        $this->assertEquals($output, $this->helper->humanReadableToBytes($input));
    }
}

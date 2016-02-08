<?php
class Klevu_Search_Test_Helper_Api extends EcomDev_PHPUnit_Test_Case {

    const VERSION_NUMBER = '1.1.12';

    public function testGetVersion() {
        $version = Mage::getConfig()->getModuleConfig('Klevu_Search')->version;
        $this->assertEquals(self::VERSION_NUMBER, $version);
    }
}

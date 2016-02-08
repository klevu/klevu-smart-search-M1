<?php
class Klevu_Boosting_Test_Model_Boost extends Klevu_Search_Test_Model_Api_Test_Case
{
   
    /**
     * @test
     * @loadFixture
     */
    public function testGetMatchingProductIds()
    {
        //test Data with attribute condition
        $model = Mage::getModel('boosting/boost')->load(466);
        $this->assertEquals(array(132), $model->getMatchingProductIds());
        
        //test Data with category condition
        $model = Mage::getModel('boosting/boost')->load(467);
        $this->assertEquals(array(132), $model->getMatchingProductIds());
    }
}

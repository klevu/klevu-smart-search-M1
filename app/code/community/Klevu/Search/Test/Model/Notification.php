<?php

class Klevu_Search_Test_Model_Notification extends EcomDev_PHPUnit_Test_Case
{

    protected function tearDown() 
    {
        $resource = Mage::getModel("core/resource");
        $resource->getConnection("core_write")->delete($resource->getTableName("klevu_search/notification"));

        parent::tearDown();
    }

    /**
     * @test
     * @loadFixture
     */
    public function testLoad() 
    {
        $notification = Mage::getModel("klevu_search/notification")->load(1);

        $this->assertEquals(1, $notification->getId());
        $this->assertEquals("2014-05-13 11:08:00", $notification->getDate());
        $this->assertEquals("test", $notification->getType());
        $this->assertEquals("Testing", $notification->getMessage());
    }

    /**
     * @test
     */
    public function testSave() 
    {
        $notification = Mage::getModel("klevu_search/notification");

        $notification->setData(
            array(
            "type" => "test",
            "message" => "Testing"
            )
        );

        $notification->save();

        $this->assertNotNull($notification->getId());

        $result = Mage::getModel("klevu_search/notification")->load($notification->getId());

        $this->assertEquals($result->getId(), $result->getId());
        $this->assertNotNull($result->getDate());
        $this->assertEquals("test", $result->getType());
        $this->assertEquals("Testing", $result->getMessage());
    }
}

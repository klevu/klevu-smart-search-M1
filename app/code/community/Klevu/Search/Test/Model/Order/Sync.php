<?php

class Klevu_Search_Test_Model_Order_Sync extends Klevu_Search_Test_Model_Api_Test_Case {

    protected function tearDown() {
        $resource = Mage::getModel("core/resource");
        $connection = $resource->getConnection("core_write");

        $connection->delete($resource->getTableName("klevu_search/order_sync"));

        parent::tearDown();
    }

    /**
     * @test
     * @loadFixture
     */
    public function testAddOrderToQueue() {
        $order = Mage::getModel("sales/order");
        $order->load(1);

        $model = Mage::getModel("klevu_search/order_sync");
        $model->addOrderToQueue($order);

        $this->assertEquals(array(array("order_item_id" => "2")), $this->getOrderSyncTableContents(),
            "Failed asserting that addOrderToQueue() adds the child configurable item to Order Sync queue."
        );
    }

    /**
     * @test
     * @loadFixture
     */
    public function testClearQueue() {
        $model = Mage::getModel("klevu_search/order_sync");

        $model->clearQueue(1);

        $this->assertEquals(array(array("order_item_id" => "3")), $this->getOrderSyncTableContents(),
            "Failed asserting that clearQueue() only removes order items for the store given."
        );

        $model->clearQueue();

        $this->assertEmpty($this->getOrderSyncTableContents(),
            "Failed asserting that clearQueue() removes all items if no store is given."
        );
    }

    /**
     * @test
     * @loadFixture
     */
    public function testRun() {
        $this->replaceApiActionByMock(
            "klevu_search/api_action_producttracking",
            Mage::getModel("klevu_search/api_response_data")->setRawResponse(
                new Zend_Http_Response(200, array(), $this->getDataFileContents("data_response_success_only.xml"))
            )
        );

        $model = $this->getModelMock("klevu_search/order_sync", array("isRunning", "isBelowMemoryLimit"));
        $model
            ->expects($this->any())
            ->method("isRunning")
            ->will($this->returnValue(false));
        $model
            ->expects($this->any())
            ->method("isBelowMemoryLimit")
            ->will($this->returnValue(true));

        $model->run();

        $this->assertEmpty($this->getOrderSyncTableContents(),
            "Failed asserting that order item gets removed from the sync queue."
        );
    }

    protected function getOrderSyncTableContents($where = null) {
        $resource = Mage::getModel("core/resource");
        $connection = $resource->getConnection("core_write");

        $select = $connection->select()->from($resource->getTableName("klevu_search/order_sync"));
        if ($where) {
            $select->where($where);
        }

        return $connection->fetchAll($select);
    }
}

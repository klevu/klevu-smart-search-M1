<?php

class Klevu_Search_Test_Model_Product_Sync extends Klevu_Search_Test_Model_Api_Test_Case {

    protected function tearDown() {
        $resource = Mage::getModel('core/resource');

        $resource->getConnection("core_write")->delete($resource->getTableName("klevu_search/product_sync"));

        Mage::getResourceModel('catalog/product_collection')->delete();

        parent::tearDown();
    }

    /**
     * @test
     * @loadFixture
     * @doNotIndexAll
     */
    public function testRun() {
        $this->reindexAll();

        $this->replaceApiActionByMock("klevu_search/api_action_startsession", $this->getSuccessfulSessionResponse());

        $model = $this->getModelMock("klevu_search/product_sync", array(
            "isBelowMemoryLimit", "deleteProducts", "updateProducts", "addProducts"
        ));
        $model
            ->expects($this->any())
            ->method("isBelowMemoryLimit")
            ->will($this->returnValue(true));
        $model
            ->expects($this->once())
            ->method("deleteProducts")
            ->with(array(
                array("product_id" => "130", "parent_id" => "0"),
                array("product_id" => "201", "parent_id" => "202"),
                array("product_id" => "211", "parent_id" => "212")
            ))
            ->will($this->returnValue(true));
        $model
            ->expects($this->once())
            ->method("updateProducts")
            ->with(array(
                array("product_id" => "133", "parent_id" => "0"),
                array("product_id" => "134", "parent_id" => "0"),
                array("product_id" => "203", "parent_id" => "204"),
                array("product_id" => "205", "parent_id" => "206")
            ))
            ->will($this->returnValue(true));
        $model
            ->expects($this->once())
            ->method("addProducts")
            ->with(array(
                array("product_id" => "132", "parent_id" => "0"),
                array("product_id" => "207", "parent_id" => "209"),
                array("product_id" => "208", "parent_id" => "209")
            ))
            ->will($this->returnValue(true));

        $model->run();
    }
    
    /**
     * @test
     * @loadFixture
     * @doNotIndexAll
     */
    public function testDeleteProducts() {
        $this->reindexAll();

        $this->replaceApiActionByMock("klevu_search/api_action_startsession", $this->getSuccessfulSessionResponse());
        $this->replaceApiActionByMock("klevu_search/api_action_deleterecords", $this->getSuccessfulMessageResponse());

        Mage::getModel('klevu_search/product_sync')->run();

        $this->assertEquals(array(), $this->getProductSyncTableContents());
    }

    /**
     * @test
     * @loadFixture
     * @doNotIndexAll
     */
    public function testUpdateProducts() {
        $this->reindexAll();

        $this->replaceApiActionByMock("klevu_search/api_action_startsession", $this->getSuccessfulSessionResponse());
        $this->replaceApiActionByMock("klevu_search/api_action_updaterecords", $this->getSuccessfulMessageResponse());

        $this->replaceSessionByMock("core/session");
        $this->replaceSessionByMock("customer/session");

        Mage::getModel('klevu_search/product_sync')->run();

        $contents = $this->getProductSyncTableContents('last_synced_at > "2008-06-27 01:57:22"');

        $this->assertTrue((is_array($contents) && count($contents) == 1));
        $this->assertEquals("133", $contents[0]['product_id']);
    }

    /**
     * @test
     * @loadFixture
     * @doNotIndexAll
     */
    public function testAddProducts() {
        $this->reindexAll();

        $this->replaceApiActionByMock("klevu_search/api_action_startsession", $this->getSuccessfulSessionResponse());
        $this->replaceApiActionByMock("klevu_search/api_action_addrecords", $this->getSuccessfulMessageResponse());

        $this->replaceSessionByMock("core/session");
        $this->replaceSessionByMock("customer/session");

        Mage::getModel('klevu_search/product_sync')->run();

        $contents = $this->getProductSyncTableContents();

        $this->assertTrue((is_array($contents) && count($contents) == 1));
        $this->assertEquals("133", $contents[0]['product_id']);
    }
    
    

    /**
     * @test
     * @loadFixture
     */
    public function testClearAllProducts() {
        $model = Mage::getModel("klevu_search/product_sync");

        $model->clearAllProducts(1);

        $contents = $this->getProductSyncTableContents();

        $this->assertTrue(
            is_array($contents) && count($contents) == 1 && $contents[0]['product_id'] == 2,
            "Failed asserting that clearAllProducts() only removes products for the given store."
        );

        $model->clearAllProducts();

        $contents = $this->getProductSyncTableContents();

        $this->assertTrue(
            empty($contents),
            "Failed asserting that clearAllProducts() removes products for all stores."
        );
    }

    /**
     * @test
     */
    public function testAutomaticAttributes() {
        $model = Mage::getModel("klevu_search/product_sync");

        $automatic_attributes = $model->getAutomaticAttributes();

        $expected_attributes = $this->getExpectedAutomaticAttributes();

        $this->assertEquals($expected_attributes, $automatic_attributes);
    }

    /**
     * Return a klevu_search/api_response_message model with a successful response from
     * a startSession API call.
     *
     * @return Klevu_Search_Model_Api_Response_Message
     */
    protected function getSuccessfulSessionResponse() {
        $model = Mage::getModel('klevu_search/api_response_message')->setRawResponse(
            new Zend_Http_Response(200, array(), $this->getDataFileContents("startsession_response_success.xml"))
        );

        return $model;
    }

    /**
     * Return a klevu_search/api_response_message model with a successful response.
     *
     * @return Klevu_Search_Model_Api_Response_Message
     */
    protected function getSuccessfulMessageResponse() {
        $model = Mage::getModel('klevu_search/api_response_message')->setRawResponse(
            new Zend_Http_Response(200, array(), $this->getDataFileContents("message_response_success.xml"))
        );

        return $model;
    }

    /**
     * Return the contents of the Product Sync table.
     *
     * @param string $where The where clause to use in the database query
     *
     * @return array
     */
    protected function getProductSyncTableContents($where = null) {
        $resource = Mage::getModel('core/resource');
        $connection = $resource->getConnection("core_write");

        $select = $connection->select()->from($resource->getTableName('klevu_search/product_sync'));
        if ($where) {
            $select->where($where);
        }

        return $connection->fetchAll($select);
    }

    /**
     * Run all of the indexers.
     *
     * @return $this
     */
    protected function reindexAll() {
        $indexer = Mage::getSingleton('index/indexer');

        // Delete all index events
        $index_events = Mage::getResourceModel("index/event_collection");
        foreach ($index_events as $event) {
            /** @var Mage_Index_Model_Event $event */
            $event->delete();
        }

        // Remove the stores cache from the category product index
        if ($process = $indexer->getProcessByCode("catalog_category_product")) {
            EcomDev_Utils_Reflection::setRestrictedPropertyValue(
                $process->getIndexer()->getResource(), "_storesInfo", null
            );
        }

        $processes = $indexer->getProcessesCollection();

        // Reset all the indexers
        foreach ($processes as $process) {
            /** @var Mage_Index_Model_Process $process */
            if ($process->hasData('runed_reindexall')) {
                $process->setData('runed_reindexall', false);
            }
        }

        // Run all indexers
        foreach ($processes as $process) {
            /** @var Mage_Index_Model_Process $process */
            $process->reindexEverything();
        }

        return $this;
    }

    protected function getExpectedAutomaticAttributes() {
        return array(
            array(
                'klevu_attribute' => 'name',
                'magento_attribute' => 'name'),
            array(
                'klevu_attribute' => 'sku',
                'magento_attribute' => 'sku'),
            array(
                'klevu_attribute' => 'image',
                'magento_attribute' => 'image'),
            array(
                'klevu_attribute' => 'desc',
                'magento_attribute' => 'description'),
            array(
                'klevu_attribute' => 'shortDesc',
                'magento_attribute' => 'short_description'),
            array(
                'klevu_attribute' => 'salePrice',
                'magento_attribute' => 'price'),
            array(
                'klevu_attribute' => 'salePrice',
                'magento_attribute' => 'tax_class_id'),
            array(
                'klevu_attribute' => 'weight',
                'magento_attribute' => 'weight'),
        );
    }
    
    /**
     * Run special price prodcuts ids
     * @test
     * @loadFixture
     */
    public function testSpecialpriceProducts()
    {
        $model = Mage::getModel("klevu_search/product_sync");
        $expirySaleProductsIds = $model->getExpirySaleProductsIds();
        $model->markProductForUpdate();
        $this->assertEquals($this->getExpectedSpecialpriceProducts(), $expirySaleProductsIds);
    }
    
    /**
     * Run special price prodcuts ids
     * @test
     * @loadFixture
     */
    public function testCatalogruleProducts()
    {

        $model = Mage::getModel("klevu_search/product_sync");
        $catalogruleProductsIds = $model->getCatalogRuleProductsIds();
        $model->markProductForUpdate();
        $this->assertEquals($this->getExpectedSpecialpriceProducts(), $catalogruleProductsIds);
        
    }
    
    /**
     * Expected prodcuts ids
     */
    public function getExpectedSpecialpriceProducts()
    {
        return array(133);
        
    }

    protected function getDataFileContents($file) {
        $directory_tree = array(
            Mage::getModuleDir('', 'Klevu_Search'),
            'Test',
            'Model',
            'Api',
            'data',
            $file
        );

        $file_path = join(DS, $directory_tree);

        return file_get_contents($file_path);
    }

    protected function getPriceAttribute() {
        return Mage::getModel('eav/entity_attribute')->loadByCode(Mage_Catalog_Model_Product::ENTITY, 'price');
    }
    
    
}

<?php

class Klevu_Content_Test_Model_Content extends Klevu_Search_Test_Model_Api_Test_Case {

    protected function tearDown() {
        $resource = Mage::getModel('core/resource');
        $resource->getConnection("core_write")->delete($resource->getTableName("klevu_search/product_sync"));
        parent::tearDown();
    }

    /**
     * @test
     * @loadFixture
     */
    public function testCmsRun() {
        $this->replaceApiActionByMock("klevu_search/api_action_startsession", $this->getSuccessfulSessionResponse());
        $model = $this->getModelMock("content/content", array(
            "isBelowMemoryLimit", "deletecms", "updatecms", "addcms"
        ));
        $model
            ->expects($this->any())
            ->method("isBelowMemoryLimit")
            ->will($this->returnValue(true));
            
        $model
            ->expects($this->once())
            ->method("deletecms")
            ->with(array(
                array("product_id" => "1", "parent_id" => "0"),
            ))
            ->will($this->returnValue(true));
        $model
            ->expects($this->once())
            ->method("updatecms")
            ->with(array(
                array("product_id" => "2", "parent_id" => "0"),

            ))
            ->will($this->returnValue(true));
        $model
            ->expects($this->once())
            ->method("addcms")
            ->with(array(
                array("product_id" => "3", "parent_id" => "0"),
            ))
            ->will($this->returnValue(true));

       Mage::getModel("content/content")->runCms();
    }
    
    /**
     * @test
     * @loadFixture
     */
    public function testDeleteCms() {

        $this->replaceApiActionByMock("klevu_search/api_action_startsession", $this->getSuccessfulSessionResponse());
        $this->replaceApiActionByMock("klevu_search/api_action_deleterecords", $this->getSuccessfulMessageResponse());
        
        $content = $this->getProductSyncTableContents();
        
        $this->assertEquals("1", $content[0]['product_id']);
    }

    /**
     * @test
     * @loadFixture
     */
    public function testUpdateCms() {

        $this->replaceApiActionByMock("klevu_search/api_action_startsession", $this->getSuccessfulSessionResponse());
        $this->replaceApiActionByMock("klevu_search/api_action_updaterecords", $this->getSuccessfulMessageResponse());

        $this->replaceSessionByMock("core/session");
        $this->replaceSessionByMock("customer/session");
        $contents = $this->getProductSyncTableContentsForUpdate();
        $this->assertEquals("2", $contents[0]['product_id']);
    }

    /**
     * @test
     * @loadFixture
     */
    public function testAddCms() {
 
        $this->replaceApiActionByMock("klevu_search/api_action_startsession", $this->getSuccessfulSessionResponse());
        $this->replaceApiActionByMock("klevu_search/api_action_addrecords", $this->getSuccessfulMessageResponse());
        
        $this->replaceSessionByMock("core/session");
        $this->replaceSessionByMock("customer/session");

        $contents = $this->getProductSyncTableContents();

        $this->assertEquals("4", $contents[0]['product_id']);
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
        $select = $connection->select()->from($resource->getTableName('klevu_search/product_sync'))
        ->where('type=?',"pages");
        if ($where) {
            $select->where($where);
        }
        return $connection->fetchAll($select);
    }

    /**
     * Return the contents of the Product Sync table.
     *
     * @param string $where The where clause to use in the database query
     *
     * @return array
     */
    protected function getProductSyncTableContentsForUpdate($where = null) {
        $resource = Mage::getModel('core/resource');
        $connection = $resource->getConnection("core_write");
        $select = $connection->select()->from($resource->getTableName('klevu_search/product_sync'));
        //->where('type=?',"pages");
        $where = 'last_synced_at > "2008-06-27 01:57:22" AND type="pages"';
        if ($where) {
            $select->where($where);
        }
        return $connection->fetchAll($select);
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
}
<?php

class Klevu_Search_Test_Model_Api_Response_Message extends Klevu_Search_Test_Model_Api_Test_Case
{

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function testIsSuccessful($response_code, $response_data_file, $is_successful) 
    {
        $http_response = new Zend_Http_Response($response_code, array(), $this->getDataFileContents($response_data_file));

        $model = Mage::getModel('klevu_search/api_response_message');
        $model->setRawResponse($http_response);

        $this->assertEquals($is_successful, $model->isSuccessful());
    }

    /**
     * @test
     */
    public function testGetSessionId() 
    {
        $http_response = new Zend_Http_Response(200, array(), $this->getDataFileContents("message_response_session_id.xml"));

        $model = Mage::getModel('klevu_search/api_response_message');
        $model->setRawResponse($http_response);

        $this->assertEquals("Klevu-session-1234567890", $model->getSessionId());
    }
}

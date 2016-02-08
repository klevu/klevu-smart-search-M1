<?php

class Klevu_Search_Test_Model_Api_Response_Data extends Klevu_Search_Test_Model_Api_Test_Case {

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function testIsSuccessful($response_code, $response_data_file, $is_successful) {
        $http_response = new Zend_Http_Response($response_code, array(), $this->getDataFileContents($response_data_file));

        $model = Mage::getModel('klevu_search/api_response_data');
        $model->setRawResponse($http_response);

        $this->assertEquals($is_successful, $model->isSuccessful());
    }

    /**
     * @test
     */
    public function testData() {
        $http_response = new Zend_Http_Response(200, array(), $this->getDataFileContents("data_response_data.xml"));

        $model = Mage::getModel('klevu_search/api_response_data');
        $model->setRawResponse($http_response);

        $this->assertEquals("test", $model->getTest(), "Failed asserting that data gets set on the response.");
        $this->assertNull($model->getResponse(), "Failed asserting that 'response' element gets removed from data.");
        $this->assertEquals("test", $model->getCamelCase(), "Failed asserting that data keys get converted from camel case.");
    }
}

<?php

class Klevu_Search_Test_Model_Api_Response_Timezone extends Klevu_Search_Test_Model_Api_Test_Case
{

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function testIsSuccessful($response_code, $response_data_file, $is_successful) 
    {
        $http_response = new Zend_Http_Response($response_code, array(), $this->getDataFileContents($response_data_file));

        $model = Mage::getModel('klevu_search/api_response_timezone');
        $model->setRawResponse($http_response);

        $this->assertEquals($is_successful, $model->isSuccessful());
    }
}

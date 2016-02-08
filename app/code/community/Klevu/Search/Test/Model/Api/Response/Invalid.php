<?php

class Klevu_Search_Test_Model_Api_Response_Invalid extends Klevu_Search_Test_Model_Api_Test_Case {

    /**
     * @test
     */
    public function testIsSuccessful() {
        $model = Mage::getModel('klevu_search/api_response_invalid');

        $this->assertEquals(
            false,
            $model->isSuccessful(),
            "Failed asserting that isSuccessful() returns false when no HTTP response is provided."
        );

        $model->setRawResponse(new Zend_Http_Response(200, array()));

        $this->assertEquals(
            false,
            $model->isSuccessful(),
            "Failed asserting that isSuccessful() returns false when given a successful HTTP response."
        );

        $model->setRawResponse(new Zend_Http_Response(500, array()));

        $this->assertEquals(
            false,
            $model->isSuccessful(),
            "Failed asserting that isSuccessful() returns false when given an unsuccessful HTTP response."
        );
    }
}

<?php

class Klevu_Search_Test_Model_Api_Action_Producttracking extends Klevu_Search_Test_Model_Api_Test_Case {

    /**
     * @test
     */
    public function testValidate() {
        $parameters = $this->getTestParameters();

        $response = Mage::getModel('klevu_search/api_response');
        $response->setRawResponse(new Zend_Http_Response(200, array(), "Test response"));

        $request = $this->getModelMock('klevu_search/api_request', array("send"));
        $request
            ->expects($this->once())
            ->method("send")
            ->will($this->returnValue($response));

        $action = $this->getModel();
        $action
            ->setRequest($request);

        $this->assertEquals($response, $action->execute($parameters));
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function testValidateRequiredFields($field) {
        $parameters = $this->getTestParameters();
        unset($parameters[$field]);

        $request = $this->getModelMock('klevu_search/api_request', array("send"));
        $request
            ->expects($this->never())
            ->method("send");

        $action = $this->getModel();
        $action
            ->setRequest($request);

        $response = $action->execute($parameters);

        $this->assertInstanceOf("Klevu_Search_Model_Api_Response_Invalid", $response);

        $this->assertArrayHasKey(
            $field,
            $response->getErrors(),
            sprintf("Failed to assert that an error is returned for %s parameter.", $field)
        );
    }

    /**
     * Return the model being tested.
     *
     * @return Klevu_Search_Model_Api_Action_Producttracking
     */
    protected function getModel() {
        return Mage::getModel('klevu_search/api_action_producttracking');
    }

    protected function getTestParameters() {
        return array(
            'klevu_apiKey'    => "test-api-key",
            'klevu_type'      => "checkout",
            'klevu_productId' => 1,
            'klevu_unit'      => 1,
            'klevu_salePrice' => 100.00,
            'klevu_currency'  => "GBP",
        );
    }

}

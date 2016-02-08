<?php

class Klevu_Search_Test_Model_Api_Action_Getuserfeaturesdetail extends Klevu_Search_Test_Model_Api_Test_Case {

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

        $action = Mage::getModel('klevu_search/api_action_features');
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

        $action = Mage::getModel('klevu_search/api_action_features');
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

    protected function getTestParameters() {
        return array(
            'restApiKey'    => "a2xldnUtMTQ1MDI3MDEyNTY2NDI0ODc6S2xldnUtMXV1Z3FwNmthbw==",
        );
    }
    
    
    /**
     * @test
    */
    public function testGetFeatures() {
        $http_response = new Zend_Http_Response(200, array(), $this->getDataFileContents("feature_response.xml"));
        $model = Mage::getModel('klevu_search/api_action_features');
        $model->setRawResponse($http_response);
        $features =  $model->getData();
        $actual_disabled_features = $features['disabled'];
        $expected_disabled_features = "boosting,enabledpopulartermfront,preserves_layout";
        $this->assertEquals($expected_disabled_features, $actual_disabled_features, 'expected features not matching with actual fearures');
    }
}

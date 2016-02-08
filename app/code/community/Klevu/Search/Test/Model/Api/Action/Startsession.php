<?php

class Klevu_Search_Test_Model_Api_Action_Startsession extends Klevu_Search_Test_Model_Api_Test_Case {

    public function testValidate() {
        $parameters = array(
            'api_key' => "dGVzdC1hcGkta2V5",
            'store'   => null
        );

        $response = Mage::getModel('klevu_search/api_response');
        $response->setRawResponse(new Zend_Http_Response(200, array(), "Test response"));

        $request = $this->getModelMock('klevu_search/api_request', array("send"));
        $request
            ->expects($this->once())
            ->method("send")
            ->will($this->returnValue($response));

        $action = Mage::getModel('klevu_search/api_action_startsession');
        $action
            ->setRequest($request);

        $this->assertEquals($response, $action->execute($parameters));

        $returned_response = $action->execute(array());

        $this->assertInstanceOf("Klevu_Search_Model_Api_Response_Invalid", $returned_response);
        $this->assertEquals(array("Missing API key."), $returned_response->getErrors());
    }
}

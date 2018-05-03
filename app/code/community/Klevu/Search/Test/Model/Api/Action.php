<?php

class Klevu_Search_Test_Model_Api_Action extends Klevu_Search_Test_Model_Api_Test_Case
{

    /**
     * @test
     */
    public function testExecute() 
    {
        $response_model = Mage::getModel('klevu_search/api_response');
        $response_model
            ->setRawResponse(new Zend_Http_Response(200, array(), "Test response"));

        $request_model = $this->getModelMock('klevu_search/api_request', array("send"));
        $request_model
            ->expects($this->once())
            ->method("send")
            ->will($this->returnValue($response_model));

        $action = Mage::getModel('klevu_search/api_action');
        $action
            ->setRequest($request_model)
            ->setResponse($response_model);

        $this->assertEquals($response_model, $action->execute());
    }

    /**
     * @test
     */
    public function testValidate() 
    {
        $errors = array("Test error", "Another test error");

        $action = $this->getModelMock('klevu_search/api_action', array("validate"));
        $action
            ->expects($this->once())
            ->method("validate")
            ->will($this->returnValue($errors));

        $request_model = $this->getModelMock('klevu_search/api_request', array("send"));
        $request_model
            ->expects($this->never())
            ->method("send");

        $action->setRequest($request_model);

        $response = $action->execute();

        $this->assertInstanceOf($this->getGroupedClassName("model", "klevu_search/api_response_invalid"), $response);
        $this->assertEquals(
            $errors,
            $response->getErrors(),
            "Returned response model does not contain the validation errors expected."
        );
    }
}

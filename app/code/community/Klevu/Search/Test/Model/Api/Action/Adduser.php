<?php

class Klevu_Search_Test_Model_Api_Action_Adduser extends Klevu_Search_Test_Model_Api_Test_Case
{

    /**
     * @test
     */
    public function testValidate() 
    {
        $parameters = $this->getTestParameters();

        $response = Mage::getModel('klevu_search/api_response');
        $response->setRawResponse(new Zend_Http_Response(200, array(), "Test response"));

        $request = $this->getModelMock('klevu_search/api_request', array("send"));
        $request
            ->expects($this->once())
            ->method("send")
            ->will($this->returnValue($response));

        $action = Mage::getModel('klevu_search/api_action_adduser');
        $action
            ->setRequest($request);

       $this->assertEquals($response, $action->execute($parameters));
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function testValidateRequiredFields($field) 
    {
        $parameters = $this->getTestParameters();
        unset($parameters[$field]);

        $request = $this->getModelMock('klevu_search/api_request', array("send"));
        $request
            ->expects($this->never())
            ->method("send");

        $action = Mage::getModel('klevu_search/api_action_adduser');
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

    protected function getTestParameters() 
    {
        return array(
            "email"    => "test@klevu.com",
            "password" => "password1",
            "url"      => "http://www.klevu.com/"
        );
    }
}

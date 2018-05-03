<?php

class Klevu_Search_Model_Api_Action_Startsession extends Klevu_Search_Model_Api_Action
{

    const ENDPOINT = "/rest/service/startSession";
    const METHOD   = "POST";

    const DEFAULT_REQUEST_MODEL  = "klevu_search/api_request_xml";
    const DEFAULT_RESPONSE_MODEL = "klevu_search/api_response_message";

    public function execute($parameters = array()) 
    {
        $validation_result = $this->validate($parameters);
        if ($validation_result !== true) {
            return Mage::getModel('klevu_search/api_response_invalid')->setErrors($validation_result);
        }

        $request = $this->getRequest();
        $endpoint = Mage::helper('klevu_search/api')->buildEndpoint(static::ENDPOINT, $parameters['store'], Mage::helper('klevu_search/config')->getRestHostname($parameters['store']));

        $request
            ->setResponseModel($this->getResponse())
            ->setEndpoint($endpoint)
            ->setMethod(static::METHOD)
            ->setHeader("Authorization", $parameters['api_key']);

        return $request->send();
    }

    protected function validate($parameters) 
    {
        if (!isset($parameters['api_key']) || empty($parameters['api_key'])) {
            return array("Missing API key.");
        } else {
            return true;
        }
    }

}

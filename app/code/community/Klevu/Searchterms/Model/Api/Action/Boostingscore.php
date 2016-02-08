<?php

class Klevu_Learning_Model_Api_Action_Boostingscore extends Klevu_Search_Model_Api_Action {

    const ENDPOINT = "/cloud-search/learningNavigation";
    const METHOD   = "POST";
    const DEFAULT_REQUEST_MODEL  = "klevu_search/api_request_post";
    const DEFAULT_RESPONSE_MODEL = "klevu_search/api_response_data";
    
    protected function validate($parameters) {

        $errors = array();
       
        if (!isset($parameters["restApiKey"]) || empty($parameters["restApiKey"])) {
            $errors["restApiKey"] = "Missing Rest API key.";
        }
        
        if (!isset($parameters["resetData"]) || empty($parameters["resetData"])) {
            $errors["resetData"] = "Missing Reset Flag.";
        }
        
        if (!isset($parameters["ack"]) || empty($parameters["ack"])) {
            $errors["ack"] = "Missing Acknowledge Flag.";
        }
  
        if (count($errors) == 0) {
            return true;
        }
        return $errors;
    }    
    /**
     * Execute the API action with the given parameters.
     *
     * @param array $parameters
     *
     * @return Klevu_Search_Model_Api_Response
     */
    public function execute($parameters = array()) {
        
        $validation_result = $this->validate($parameters);
        if ($validation_result !== true) {
            return Mage::getModel('klevu_search/api_response_invalid')->setErrors($validation_result);
        }
        
        $request = $this->getRequest();
        $endpoint = Mage::helper('klevu_search/api')->buildEndpoint(static::ENDPOINT, $this->getStore(), Mage::helper('learning')->getLearningUrl($this->getStore()));
        $request
            ->setResponseModel($this->getResponse())
            ->setEndpoint($endpoint)
            ->setMethod(static::METHOD)
            ->setData($parameters);
        return $request->send();
       
    }
}

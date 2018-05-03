<?php

class Klevu_Search_Model_Api_Action_Getplans extends Klevu_Search_Model_Api_Action
{

    const ENDPOINT = "/analytics/getBillingDetailsOfUser";
    const METHOD   = "POST";
    const DEFAULT_REQUEST_MODEL  = "klevu_search/api_request_post";
    const DEFAULT_RESPONSE_MODEL = "klevu_search/api_response_data";
    
    protected function validate($parameters) 
    {

        $errors = array();
       
        if (!isset($parameters["store"])) {
            $errors["store"] = "Missing store value.";
        }
        
        if (!isset($parameters["extension_version"])) {
            $errors["extension_version"] = "Missing extension version.";
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
    public function execute($parameters = array()) 
    {
        
        $validation_result = $this->validate($parameters);
        if ($validation_result !== true) {
            return Mage::getModel('klevu_search/api_response_invalid')->setErrors($validation_result);
        }

        $request = $this->getRequest();
        $endpoint = Mage::helper('klevu_search/api')->buildEndpoint(static::ENDPOINT, $this->getStore(), Mage::helper('klevu_search/config')->getHostname($this->getStore()));
        $request
            ->setResponseModel($this->getResponse())
            ->setEndpoint($endpoint)
            ->setMethod(static::METHOD)
            ->setData($parameters);
        return $request->send();
       
    }
}

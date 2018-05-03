<?php

class Klevu_Search_Model_Api_Action_Features extends Klevu_Search_Model_Api_Action
{

    const ENDPOINT = "/uti/getFeaturesAndUpgradeLink";
    const METHOD   = "POST";
    const DEFAULT_REQUEST_MODEL  = "klevu_search/api_request_post";
    const DEFAULT_RESPONSE_MODEL = "klevu_search/api_response_data";
    
    protected function validate($parameters) 
    {

        $errors = array();
       
        if (!isset($parameters["restApiKey"]) || empty($parameters["restApiKey"])) {
            $errors["restApiKey"] = "Missing Rest API key.";
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
        $store = Mage::getModel('core/store')->load($parameters['store']);
        $endpoint = Mage::helper('klevu_search/api')->buildEndpoint(static::ENDPOINT, $store, Mage::helper('klevu_search/config')->getTiresUrl($store));
        $request
            ->setResponseModel($this->getResponse())
            ->setEndpoint($endpoint)
            ->setMethod(static::METHOD)
            ->setData($parameters);
        return $request->send();
       
    }
}

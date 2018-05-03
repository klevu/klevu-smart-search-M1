<?php

class Klevu_Search_Model_Api_Action extends Varien_Object
{

    const ENDPOINT = "";
    const METHOD   = "GET";

    const DEFAULT_REQUEST_MODEL  = "klevu_search/api_request";
    const DEFAULT_RESPONSE_MODEL = "klevu_search/api_response";

    /** @var Klevu_Search_Model_Api_Request $request */
    protected $request;

    /** @var Klevu_Search_Model_Api_Response $response */
    protected $response;

    /**
     * Set the request model to use for this API action.
     *
     * @param Klevu_Search_Model_Api_Request $request_model
     *
     * @return $this
     */
    public function setRequest(Klevu_Search_Model_Api_Request $request_model) 
    {
        $this->request = $request_model;

        return $this;
    }

    /**
     * Return the request model used for this API action.
     *
     * @return Klevu_Search_Model_Api_Request
     */
    public function getRequest() 
    {
        if (!$this->request) {
            $this->request = Mage::getModel(static::DEFAULT_REQUEST_MODEL);
        }

        return $this->request;
    }

    /**
     * Set the response model to use for this API action.
     *
     * @param Klevu_Search_Model_Api_Response $response_model
     *
     * @return $this
     */
    public function setResponse(Klevu_Search_Model_Api_Response $response_model) 
    {
        $this->response = $response_model;

        return $this;
    }

    /**
     * Return the response model used for this API action.
     *
     * @return Klevu_Search_Model_Api_Response
     */
    public function getResponse() 
    {
        if (!$this->response) {
            $this->response = Mage::getModel(static::DEFAULT_RESPONSE_MODEL);
        }

        return $this->response;
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

    /**
     * Get the store used for this request
     * @return Mage_Core_Model_Store
     */
    public function getStore() 
    {
        if(!$this->hasData('store')) {
            $this->setData('store', Mage::app()->getStore());
        }

        return $this->getData('store');
    }

    /**
     * Validate the given parameters against the API action specification and
     * return true if validation passed or an array of validation error messages
     * otherwise.
     *
     * @param $parameters
     *
     * @return bool|array
     */
    protected function validate($parameters) 
    {
        return true;
    }
}

<?php

class Klevu_Search_Model_Api_Request extends Varien_Object
{

    protected $endpoint;

    protected $method;

    protected $headers;

    protected $response_model;

    public function _construct() 
    {
        parent::_construct();

        $this->method = Zend_Http_Client::GET;
        $this->headers = array();
        $this->response_model = Mage::getModel('klevu_search/api_response');
    }

    /**
     * Set the target endpoint URL for this API request.
     *
     * @param $url
     *
     * @return $this
     */
    public function setEndpoint($url) 
    {
        $this->endpoint = $url;

        return $this;
    }

    /**
     * Return the target endpoint for this API request.
     *
     * @return string
     */
    public function getEndpoint() 
    {
        return $this->endpoint;
    }

    /**
     * Set the HTTP method to use for this API request.
     *
     * @param $method
     *
     * @return $this
     */
    public function setMethod($method) 
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Get the HTTP method configured for this API request.
     *
     * @return mixed
     */
    public function getMethod() 
    {
        return $this->method;
    }

    /**
     * Set a HTTP header for this API request.
     *
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function setHeader($name, $value) 
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Get the array of HTTP headers configured for this API request.
     *
     * @return array
     */
    public function getHeaders() 
    {
        return $this->headers;
    }

    /**
     * Set the response model to use for this API request.
     *
     * @param Klevu_Search_Model_Api_Response $response_model
     *
     * @return $this
     */
    public function setResponseModel(Klevu_Search_Model_Api_Response $response_model) 
    {
        $this->response_model = $response_model;

        return $this;
    }

    /**
     * Return the response model used for this API request.
     *
     * @return Klevu_Search_Model_Api_Response
     */
    public function getResponseModel() 
    {
        return $this->response_model;
    }

    /**
     * Perform the API request and return the received response.
     *
     * @return Klevu_Search_Model_Api_Response
     */
    public function send() 
    {
        if (!$this->getEndpoint()) {
            // Can't make a request without a URL
            Mage::throwException("Unable to send a Klevu Search API request: No URL specified.");
        }

        $raw_request = $this->build();
        Mage::helper('klevu_search')->log(Zend_Log::DEBUG, sprintf("API request:\n%s", $this->__toString()));

        try {
            $raw_response = $raw_request->request();
        } catch (Zend_Http_Client_Exception $e) {
            // Return an empty response
            Mage::helper('klevu_search')->log(Zend_Log::ERR, sprintf("HTTP error: %s", $e->getMessage()));
            return Mage::getModel('klevu_search/api_response_empty');
        }

        Mage::helper('klevu_search')->log(
            Zend_Log::DEBUG, sprintf(
                "API response:\n%s\n%s",
                $raw_response->getHeadersAsString(true, "\n"),
                $raw_response->getBody()
            )
        );

        $response = $this->getResponseModel();
        $response->setRawResponse($raw_response);

        return $response;
    }

    /**
     * Return the string representation of the API request.
     *
     * @return string
     */
    public function __toString() 
    {
        $headers = $this->getHeaders();
        if (count($headers) > 0) {
            array_walk(
                $headers, function (&$value, $key) {
                $value = ($value !== null && $value !== false) ? sprintf("%s: %s", $key, $value) : null;
                }
            );
        }

        return sprintf(
            "%s %s\n%s\n",
            $this->getMethod(),
            $this->getEndpoint(),
            implode("\n", array_filter($headers))
        );
    }

    /**
     * Build the HTTP request to be sent.
     *
     * @return Zend_Http_Client
     */
    protected function build() 
    {
        $client = new Zend_Http_Client();
        $config = array('timeout' => 60);
        $client->setConfig($config);
        $client
            ->setUri($this->getEndpoint())
            ->setMethod($this->getMethod())
            ->setHeaders($this->getHeaders());

        return $client;
    }
}

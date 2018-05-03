<?php

/**
 * Class Klevu_Search_Model_Api_Response
 *
 * @method setMessage($message)
 * @method getMessage()
 */
class Klevu_Search_Model_Api_Response extends Varien_Object
{

    protected $raw_response;

    protected $successful;
    protected $xml;

    public function _construct() 
    {
        parent::_construct();

        $this->successful = false;
    }

    /**
     * Set the raw response object representing this API response.
     *
     * @param Zend_Http_Response $response
     *
     * @return $this
     */
    public function setRawResponse(Zend_Http_Response $response) 
    {
        $this->raw_response = $response;

        $this->parseRawResponse($response);

        return $this;
    }

    /**
     * Check if the API response indicates success.
     *
     * @return boolean
     */
    public function isSuccessful() 
    {
        return $this->successful;
    }

    /**
     * Return the response XML content.
     *
     * @return SimpleXMLElement
     */
    public function getXml() 
    {
        return $this->xml;
    }

    /**
     * Extract the API response data from the given HTTP response object.
     *
     * @param Zend_Http_Response $response
     *
     * @return $this
     */
    protected function parseRawResponse(Zend_Http_Response $response) 
    {
        if ($response->isSuccessful()) {
            $content = $response->getBody();

            if (strlen($content) > 0) {
                try {
                    $xml = simplexml_load_string($response->getBody());
                } catch (Exception $e) {
                    // Failed to parse XML
                    $this->successful = false;
                    $this->setMessage("Failed to parse a response from Klevu.");
                    Mage::helper('klevu_search')->log(Zend_Log::ERR, sprintf("Failed to parse XML response: %s", $e->getMessage()));
                    return $this;
                }

                $this->xml = $xml;
                $this->successful = true;
            } else {
                // Response contains no content
                $this->successful = false;
                $this->setMessage('Failed to parse a response from Klevu.');
                Mage::helper('klevu_search')->log(Zend_Log::ERR, "API response content is empty.");
            }
        } else {
            // Unsuccessful HTTP response
            $this->successful = false;
            switch ($response->getStatus()) {
                case 403:
                    $message = "Incorrect API keys.";
                    break;
                case 500:
                    $message = "API server error.";
                    break;
                case 503:
                    $message = "API server unavailable.";
                    break;
                case 400:
                    $message = "Klevu Product sync has issues indexing your products. <b>".Mage::helper('klevu_search')->getBaseDomain()."</b> is not listed as an allowed base URL for the Klevu Search API key <b>'".Mage::helper('klevu_search/config')->getJsApiKey()."'</b>. Please <a href='http://support.klevu.com/knowledgebase/base-urls
' target='_blank'>click here</a> for more information.";
                    break;
                default:
                    $message = "Unexpected error.";
            }

            if($response->getStatus() == 400) {
                $this->setMessage(sprintf("%s", $message));
                $storefromscope = Mage::app()->getStore(Mage::helper('klevu_search/config')->scopeId());
                Mage::getModel('klevu_search/product_sync')->notify(
                    Mage::helper('klevu_search')->__(
                        "Product Sync failed for %s (%s): %s",
                        $storefromscope->getWebsite()->getName(),
                        $storefromscope->getName(),
                        $message
                    ), null
                );
            } else {
                $this->setMessage(sprintf("Failed to connect to Klevu: %s", $message));
            }

            Mage::helper('klevu_search')->log(Zend_Log::ERR, sprintf("Unsuccessful HTTP response: %s %s", $response->getStatus(), $response->responseCodeAsText($response->getStatus())));
        }

        return $this;
    }
}

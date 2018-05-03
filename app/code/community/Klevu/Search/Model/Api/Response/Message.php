<?php

class Klevu_Search_Model_Api_Response_Message extends Klevu_Search_Model_Api_Response
{

    protected function parseRawResponse(Zend_Http_Response $response) 
    {
        parent::parseRawResponse($response);

        if ($this->isSuccessful()) {
            $xml = $this->getXml();

            if (isset($xml->status) && strtolower($xml->status) === "success") {
                $this->successful = true;
            } else {
                $this->successful = false;
            }

            if (isset($xml->msg)) {
                $this->setMessage((string) $xml->msg);
            }

            if (isset($xml->sessionId)) {
                $this->setSessionId((string) $xml->sessionId);
            }
        }

        return $this;
    }
}

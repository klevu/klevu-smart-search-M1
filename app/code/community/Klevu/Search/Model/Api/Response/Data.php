<?php

class Klevu_Search_Model_Api_Response_Data extends Klevu_Search_Model_Api_Response {

    protected function parseRawResponse(Zend_Http_Response $response) {
        parent::parseRawResponse($response);

        if ($this->isSuccessful()) {
            $data = $this->xmlToArray($this->getXml());

            $this->successful = false;
            if (isset($data['response'])) {
                if (strtolower($data['response']) == 'success') {
                    $this->successful = true;
                }
                unset($data['response']);
            }

            foreach ($data as $key => $value) {
                $this->setData($this->_underscore($key), $value);
            }
        }

        return $this;
    }

    /**
     * Convert XML to an array.
     *
     * @param SimpleXMLElement $xml
     *
     * @return array
     */
    protected function xmlToArray(SimpleXMLElement $xml) {
        return json_decode(json_encode($xml), true);
    }
}

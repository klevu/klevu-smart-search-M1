<?php

class Klevu_Search_Model_Api_Response_Timezone extends Klevu_Search_Model_Api_Response_Data
{

    protected function parseRawResponse(Zend_Http_Response $response) 
    {
        parent::parseRawResponse($response);

        // Timezone responses don't have a status parameters, just data
        // So the presence of the data is the status
        if ($this->hasData('timezone')) {
            $this->successful = true;
        } else {
            $this->successful = false;
        }
    }
}

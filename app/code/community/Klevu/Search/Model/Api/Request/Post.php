<?php

class Klevu_Search_Model_Api_Request_Post extends Klevu_Search_Model_Api_Request
{

    public function __toString() 
    {
        $string = parent::__toString();

        $parameters = $this->getData();
        if (count($parameters) > 0) {
            array_walk(
                $parameters, function (&$value, $key) {
                $value = sprintf("%s: %s", $key, $value);
                }
            );
        }

        return sprintf("%s\nPOST parameters:\n%s\n", $string, implode("\n", $parameters));
    }

    /**
     * Add POST parameters to the request, force POST method.
     *
     * @return Zend_Http_Client
     */
    protected function build() 
    {
        $client = parent::build();

        $client
            ->setMethod(Zend_Http_Client::POST)
            ->setParameterPost($this->getData());

        return $client;
    }
}

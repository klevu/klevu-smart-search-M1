<?php

class Klevu_Search_Model_Api_Request_Get extends Klevu_Search_Model_Api_Request {

    public function __toString() {
        $string = parent::__toString();

        $parameters = $this->getData();
        if (count($parameters) > 0) {
            array_walk($parameters, function(&$value, $key) {
                $value = sprintf("%s: %s", $key, $value);
            });
        }

        return sprintf("%s\nGET parameters:\n%s\n", $string, implode("\n", $parameters));
    }

    /**
     * Add GET parameters to the request, force GET method.
     *
     * @return Zend_Http_Client
     */
    protected function build() {
        $client = parent::build();

        $client
            ->setMethod("GET")
            ->setParameterGet($this->getData());

        return $client;
    }
}

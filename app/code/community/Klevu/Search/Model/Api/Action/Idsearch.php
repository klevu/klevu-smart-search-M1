<?php

class Klevu_Search_Model_Api_Action_Idsearch extends Klevu_Search_Model_Api_Action {
    const ENDPOINT = "/cloud-search/n-search/idsearch";
    const METHOD   = "GET";

    const DEFAULT_REQUEST_MODEL = "klevu_search/api_request_get";
    const DEFAULT_RESPONSE_MODEL = "klevu_search/api_response_search";

    protected function validate($parameters) {
        $errors = array();

        if (!isset($parameters['ticket']) || empty($parameters['ticket'])) {
            $errors['ticket'] = "Missing ticket (Search API Key)";
        }

        if (!isset($parameters['noOfResults']) || empty($parameters['noOfResults'])) {
            $errors['noOfResults'] = "Missing number of results to return";
        }

        if(!isset($parameters['term']) || empty($parameters['term'])) {
            $errors['term'] = "Missing search term";
        }

        if(!isset($parameters['paginationStartsFrom'])) {
            $errors['paginationStartsFrom'] = "Missing pagination start from value ";
        } else if (intval($parameters['paginationStartsFrom']) < 0) {
            $errors['paginationStartsFrom'] = "Pagination needs to start from 0 or higher";
        }

        if(!isset($parameters['klevuSort']) || empty($parameters['klevuSort'])) {
            $errors['klevuSort'] = "Missing Klevu Sort order";
        }

        if(!isset($parameters['enableFilters']) || empty($parameters['enableFilters'])) {
            $errors['enableFilters'] = "Missing Enable Filters parameter";
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
    public function execute($parameters = array()) {
        $validation_result = $this->validate($parameters);
        if ($validation_result !== true) {
            return Mage::getModel('klevu_search/api_response_invalid')->setErrors($validation_result);
        }

        $request = $this->getRequest();

        $endpoint = Mage::helper('klevu_search/api')->buildEndpoint(static::ENDPOINT, $this->getStore(), Mage::helper('klevu_search/config')->getCloudSearchUrl($this->getStore()));

        $request
            ->setResponseModel($this->getResponse())
            ->setEndpoint($endpoint)
            ->setMethod(static::METHOD)
            ->setData($parameters);

        return $request->send();
    }
}

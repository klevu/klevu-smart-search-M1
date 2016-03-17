<?php

class Klevu_Search_Model_Api_Action_Producttracking extends Klevu_Search_Model_Api_Action {

    const ENDPOINT = "/analytics/productTracking";
    const METHOD   = "POST";

    const DEFAULT_REQUEST_MODEL  = "klevu_search/api_request_get";
    const DEFAULT_RESPONSE_MODEL = "klevu_search/api_response_data";

    protected function validate($parameters) {
        $errors = array();

        if (!isset($parameters["klevu_apiKey"]) || empty($parameters["klevu_apiKey"])) {
            $errors["klevu_apiKey"] = "Missing JS API key.";
        }

        if (!isset($parameters["klevu_type"]) || empty($parameters["klevu_type"])) {
            $errors["klevu_type"] = "Missing type.";
        }

        if (!isset($parameters["klevu_productId"]) || empty($parameters["klevu_productId"])) {
            $errors["klevu_productId"] = "Missing product ID.";
        }

        if (!isset($parameters["klevu_unit"]) || empty($parameters["klevu_unit"])) {
            $errors["klevu_unit"] = "Missing unit.";
        }

        if (!isset($parameters["klevu_salePrice"]) || empty($parameters["klevu_salePrice"])) {
            $errors["klevu_salePrice"] = "Missing sale price.";
        }

        if (!isset($parameters["klevu_currency"]) || empty($parameters["klevu_currency"])) {
            $errors["klevu_currency"] = "Missing currency.";
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

        $endpoint = Mage::helper('klevu_search/api')->buildEndpoint(
            static::ENDPOINT,
            $this->getStore(),
            Mage::helper('klevu_search/config')->getAnalyticsUrl()
        );

        $request
            ->setResponseModel($this->getResponse())
            ->setEndpoint($endpoint)
            ->setMethod(static::METHOD)
            ->setData($parameters);

        return $request->send();
    }
}

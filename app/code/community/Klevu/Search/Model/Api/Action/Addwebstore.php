<?php

class Klevu_Search_Model_Api_Action_Addwebstore extends Klevu_Search_Model_Api_Action {

    const ENDPOINT = "/n-search/addWebstore";
    const METHOD   = "POST";

    const DEFAULT_REQUEST_MODEL  = "klevu_search/api_request_post";
    const DEFAULT_RESPONSE_MODEL = "klevu_search/api_response_data";

    protected function validate($parameters) {
        $errors = array();

        if (!isset($parameters['customerId']) || empty($parameters['customerId'])) {
            $errors['customerId'] = "Missing customer id.";
        }

        if (!isset($parameters['testMode']) || empty($parameters['testMode'])) {
            $errors['testMode'] = "Missing test mode.";
        } else {
            if (!in_array($parameters['testMode'], array("true", "false"))) {
                $errors['testMode'] = "Test mode must contain the text true or false.";
            }
        }

        if (!isset($parameters['storeName']) || empty($parameters['storeName'])) {
            $errors['storeName'] = "Missing store name.";
        }

        if (!isset($parameters['language']) || empty($parameters['language'])) {
            $errors['language'] = "Missing language.";
        }

        if (!isset($parameters['timezone']) || empty($parameters['timezone'])) {
            $errors['timezone'] = "Missing timezone.";
        }

        if (!isset($parameters['version']) || empty($parameters['version'])) {
            $errors['version'] = "Missing module version";
        }

        if (!isset($parameters['country']) || empty($parameters['country'])) {
            $errors['country'] = "Missing country.";
        }

        if (!isset($parameters['locale']) || empty($parameters['locale'])) {
            $errors['locale'] = "Missing locale.";
        }

        if (count($errors) == 0) {
            return true;
        }

        return $errors;
    }
}

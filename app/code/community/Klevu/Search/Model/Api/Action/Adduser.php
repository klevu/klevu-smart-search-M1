<?php

class Klevu_Search_Model_Api_Action_Adduser extends Klevu_Search_Model_Api_Action {

    const ENDPOINT = "/n-search/addUser";
    const METHOD   = "POST";

    const DEFAULT_REQUEST_MODEL  = "klevu_search/api_request_post";
    const DEFAULT_RESPONSE_MODEL = "klevu_search/api_response_data";

    protected function validate($parameters) {
        $errors = array();

        if (!isset($parameters['email']) || empty($parameters['email'])) {
            $errors['email'] = "Missing email";
        }

        if (!isset($parameters['password']) || empty($parameters['password'])) {
            $errors['password'] = "Missing password";
        }

        if (!isset($parameters['url']) || empty($parameters['password'])) {
            $errors['url'] = "Missing url";
        }

        if (count($errors) == 0) {
            return true;
        }

        return $errors;
    }
}

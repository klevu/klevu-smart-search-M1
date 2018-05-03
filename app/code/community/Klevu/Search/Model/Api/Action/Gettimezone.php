<?php

class Klevu_Search_Model_Api_Action_Gettimezone extends Klevu_Search_Model_Api_Action
{

    const ENDPOINT = "/analytics/getTimezone";
    const METHOD   = "POST";

    const DEFAULT_REQUEST_MODEL  = "klevu_search/api_request_post";
    const DEFAULT_RESPONSE_MODEL = "klevu_search/api_response_timezone";

    protected function validate($parameters) 
    {
        return true;
    }
}

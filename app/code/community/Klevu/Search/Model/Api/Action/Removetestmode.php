<?php
class Klevu_Search_Model_Api_Action_Removetestmode extends Klevu_Search_Model_Api_Action {  
  
    const ENDPOINT = "/n-search/changeWebstoreMode";
    const METHOD   = "POST";
    
    const DEFAULT_REQUEST_MODEL  = "klevu_search/api_request_post";
    const DEFAULT_RESPONSE_MODEL = "klevu_search/api_response_data";
	
	public function removeTestMode($parameters)
	{
       $endpoint = Mage::helper('klevu_search/api')->buildEndpoint(static::ENDPOINT);
	   $response = $this->getResponse();
	   $request = $this->getRequest();
       $request
            ->setResponseModel($response)
            ->setEndpoint($endpoint)
            ->setMethod(static::METHOD)
            ->setData($parameters);
        return $request->send();
       
	
	}
}

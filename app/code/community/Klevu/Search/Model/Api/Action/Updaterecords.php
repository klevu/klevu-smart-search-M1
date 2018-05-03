<?php

class Klevu_Search_Model_Api_Action_Updaterecords extends Klevu_Search_Model_Api_Action_Addrecords
{

    const ENDPOINT = "/rest/service/updateRecords";
    const METHOD   = "POST";

    // mandatory_field_name => allowed_empty
    protected $mandatory_fields = array(
        "id" => false
    );
}

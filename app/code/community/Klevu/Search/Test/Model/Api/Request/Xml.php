<?php

class Klevu_Search_Test_Model_Api_Request_Xml extends Klevu_Search_Test_Model_Api_Test_Case
{

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function testGetDataAsXml($data, $xml) 
    {
        $request = Mage::getModel('klevu_search/api_request_xml');

        $request->setData($data);

        $this->assertEquals($xml, preg_replace("/\n/", "", $request->getDataAsXml()));
    }
}

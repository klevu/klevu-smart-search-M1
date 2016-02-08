<?php

class Klevu_Content_Test_Controller_ContentSearch extends EcomDev_PHPUnit_Test_Case_Controller {

   /**
     * Run search and make sure the page returns HTTP Code 200
     * @test
     * @loadFixture content_search_results
     */
    public function testSearchResultsPageLoads() {
        $this->mockAndDispatchSearchResults();
        // Assert the request was successful.
        $this->assertResponseHttpCode(200);
    }
    
    /**
     * @param string $query
     * @return $this
     * @throws Zend_Controller_Exception
     */
    protected function mockAndDispatchSearchResults($query = 'example', $response_type = 'successful', $pagination = 0) {
        $this->mockApiAndCollection($query, $response_type, $pagination);
        // Set the search query
        $this->app()->getRequest()->setQuery('q', $query);
        // Load the search results page
        return $this->dispatch('content/index/index');
    }
    
    
    protected function mockApiAndCollection($query = 'example', $response_type = 'successful', $pagination = 0) {
        // Mock the API Action
        switch($response_type) {
            default:
            case 'successful':
                $response = $this->getSearchResponse('search_response_success.xml');
                break;
            case 'empty':
                echo "new";
                exit;
                $response = $this->getSearchResponse('search_response_empty.xml');
                break;
        }

        $this->replaceApiActionByMock("klevu_search/api_action_idsearch", $response);

    }
    
    
    /**
     * Return a klevu_search/api_response_message model with a successful response from
     * a startSession API call.
     *
     * @return Klevu_Search_Model_Api_Response_Message
     */
    protected function getSearchResponse($data_file) {
        $model = Mage::getModel('klevu_search/api_response_search')->setRawResponse(
            new Zend_Http_Response(200, array(), $this->getDataFileContents($data_file))
        );

        return $model;

    }

    protected function getDataFileContents($file) {
        $directory_tree = array(
            Mage::getModuleDir('', 'Klevu_Content'),
            'Test',
            'Model',
            'Api',
            'data',
            $file
        );

        $file_path = join(DS, $directory_tree);

        return file_get_contents($file_path);
    }
    
    
    /**
     * Create a mock class of the given API action model which will expect to be executed
     * once and will return the given response. Then replace that model in Magento with
     * the created mock.
     *
     * @param string $alias A grouped class name of the API action model to mock
     * @param Klevu_Search_Model_Api_Response $response
     *
     * @return $this
     */
    protected function replaceApiActionByMock($alias, $response) {
        $mock = $this->getModelMock($alias, array("execute"));
        $mock
            ->expects($this->any())
            ->method("execute")
            ->will($this->returnValue($response));

        $this->replaceByMock("model", $alias, $mock);
        return $this;
    }
    
}

<?php

class Klevu_Search_Model_CatalogSearch_Resource_Fulltext_Collection extends Mage_CatalogSearch_Model_Mysql4_Fulltext_Collection
{

   /**
     * Klevu Search API Parameters
     * @var array
     */
    protected $_klevu_parameters;
    protected $_klevu_tracking_parameters;
    protected $_klevu_type_of_records = 'KLEVU_PRODUCT';

    /**
     * Klevu Search API Product IDs
     * @var array
     */
    protected $_klevu_product_ids = array();
    protected $_klevu_parent_child_ids = array();
    protected $_isSearchFiltersApplied = true;

    /**
     * Klevu Search API Response
     * @var Klevu_Search_Model_Api_Response
     */
    protected $_klevu_response;
    /**
     * Search query
     * @var string
     */
    protected $_query;

    /**
     * Total number of results found
     * @var int
     */
    protected $_klevu_size;
    /**
     * The XML Response from Klevu
     * @var SimpleXMLElement
     */
    protected $_klevu_response_xml;

    /**
     * Retrieve query model object
     *
     * @return Mage_CatalogSearch_Model_Query
     */
    protected function _getQuery()
    {
        return Mage::helper('catalogsearch')->getQuery();
    }
    
    /**
     * Return the Klevu api search filters
     * @return array
     */
    public function getSearchTracking($noOfTrackingResults,$queryType) {

        $this->_klevu_tracking_parameters = array(
            'klevu_apiKey' => Mage::helper('klevu_search/config')->getJsApiKey(),
            'klevu_term' => $this->_getQuery()->getQueryText(),
            'klevu_totalResults' => $noOfTrackingResults,
            'klevu_shopperIP' => Mage::helper('klevu_search')->getIp(),
            'klevu_typeOfQuery' => $queryType,
            'Klevu_typeOfRecord' => 'KLEVU_PRODUCT'
        );
        $this->log(Zend_Log::DEBUG, sprintf("Search tracking for term: %s", $this->_getQuery()->getQueryText()));
        return $this->_klevu_tracking_parameters;
    }
    
    /**
     * This method executes the the Klevu API request if it has not already been called, and takes the result
     * with the result we get all the item IDs, pass into our helper which returns the child and parent id's.
     * We then add all these values to our class variable $_klevu_product_ids.
     *
     * @return array
     */
    protected function _getProductIds() {
        if (empty($this->_klevu_product_ids)) {

            // If no results, return an empty array
            if (!$this->getKlevuResponse()->hasData('result')) {
                return array();
            }
          
            foreach ($this->getKlevuResponse()->getData('result') as $result) {
                $item_id =  Mage::helper('klevu_search')->getMagentoProductId((string) $result['id']);
                $this->_klevu_parent_child_ids[] = $item_id;
                if ($item_id['parent_id'] != 0) {
                    $this->_klevu_product_ids[$item_id['parent_id']] = $item_id['parent_id'];
                }

                $this->_klevu_product_ids[$item_id['product_id']] = $item_id['product_id'];
            }
            $this->_klevu_product_ids = array_unique($this->_klevu_product_ids);
            $this->log(Zend_Log::DEBUG, sprintf("Products count returned: %s", count($this->_klevu_product_ids)));
            $response_meta = $this->getKlevuResponse()->getData('meta');
            Mage::getModel('klevu_search/api_action_searchtermtracking')->execute($this->getSearchTracking(count($this->_klevu_product_ids),$response_meta['typeOfQuery']));

           
        }
       return $this->_klevu_product_ids;
    }
    
    
    /**
     * Return the Klevu api search filters
     * @return array
     */
    public function getSearchFilters() {
        if (empty($this->_klevu_parameters)) {

            $this->_klevu_parameters = array(
                'ticket' => Mage::helper('klevu_search/config')->getJsApiKey(),
                'noOfResults' => 2000,
                'term' => $this->_getQuery()->getQueryText(),
                'paginationStartsFrom' => 0,
                'enableFilters' => 'false',
                'klevuShowOutOfStockProducts' =>'true',
                'category' => $this->_klevu_type_of_records

            );
        }
        
        return $this->_klevu_parameters;
    }
    
    /**
     * Send the API Request and return the API Response.
     * @return Klevu_Search_Model_Api_Response
     */
    public function getKlevuResponse() {
        if (!$this->_klevu_response) {
            $this->_klevu_response = Mage::getModel('klevu_search/api_action_idsearch')->execute($this->getSearchFilters());
        }
        return $this->_klevu_response;
    }


    /**
     * Add search query filter
     *
     * @param string $query
     * @return Mage_CatalogSearch_Model_Resource_Fulltext_Collection
     */
    public function addSearchFilter($query)
    {  
       
        Mage::getSingleton('catalogsearch/fulltext')->prepareResult();
        $queryterm = Mage::getSingleton('core/session')->getData('queryterm'); 
        $sess = isset($queryterm) ? $queryterm : '';
        if($this->_getQuery()->getQueryText() != $sess)
        {
            Mage::getSingleton('core/session')->setData('ids', $this->_getProductIds());
            Mage::getSingleton('core/session')->setData('queryterm', $this->_getQuery()->getQueryText());
        }
        
        $this->addFieldToFilter('entity_id', array('in' => Mage::getSingleton('core/session')->getData('ids')));
        return $this;
    }
    /**
     * Set Order field
     *
     * @param string $attribute
     * @param string $dir
     * @return Mage_CatalogSearch_Model_Resource_Fulltext_Collection
     */
    public function setOrder($attribute, $dir = 'desc')
    {
        if ($attribute == 'relevance') {
            $this->getSelect()->reset(Zend_Db_Select::ORDER);
            if (count(Mage::getSingleton('core/session')->getData('ids'))) {
                // Use "FIELD (column, 1[,2,3,4]) ASC" for ordering, where "1[,2,3,4]" is the list of IDs in the order required
                $this->getSelect()->order(sprintf('FIELD(`e`.`entity_id`, %s) ASC', implode(',', Mage::getSingleton('core/session')->getData('ids'))));
            }
            
        } else {
            parent::setOrder($attribute, $dir);
        }
        return $this;
    }
    
    /**
     * Stub method for campatibility with other search engines
     *
     * @return Mage_CatalogSearch_Model_Resource_Fulltext_Collection
     */
    public function setGeneralDefaultQuery()
    {
        return $this;
    }
    
    protected function log($level, $message) {
        Mage::helper('klevu_search')->log($level, $message);
    }
    
}

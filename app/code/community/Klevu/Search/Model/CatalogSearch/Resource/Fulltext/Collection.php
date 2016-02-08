<?php

class Klevu_Search_Model_CatalogSearch_Resource_Fulltext_Collection extends Mage_CatalogSearch_Model_Mysql4_Fulltext_Collection {

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
     * Prepare the search query for Klevu Search API Call
     *
     * @param string $query
     * @return $this|Mage_CatalogSearch_Model_Resource_Fulltext_Collection
     */
    public function addSearchFilter($query) {
        if (!$this->isExtensionConfigured()) {
            return parent::addSearchFilter($query);
        }

        $this->_query = $query;
        return $this;
    }

    /**
     * Stub method to prevent sort order being changed.
     * @param string $attribute
     * @param string $dir
     * @return $this|Mage_CatalogSearch_Model_Resource_Fulltext_Collection
     */
    public function setOrder($attribute, $dir = 'desc')
    {
        if (!$this->isExtensionConfigured()) {
            return parent::setOrder($attribute, $dir);
        }

        return $this;
    }


    /**
     * Return the Klevu api search filters
     * @return array
     */
    public function getSearchFilters() {
        if (empty($this->_klevu_parameters)) {

            $noOfResults = $this->getPageSize();

            // If getPageSize() returns false, we need to get the page size from the toolbar block.
            // Rather than re-writing our own version.
            if(!$noOfResults) {
                /** @var Mage_Catalog_Block_Product_List $productListBlock */
                $productListBlock = Mage::getBlockSingleton('catalog/product_list');
                $toolbarBlock = $productListBlock->getToolbarBlock();
                $noOfResults = (int) $toolbarBlock->getLimit();
            }

            $page = Mage::app()->getRequest()->getParam('p');
            $this->_klevu_parameters = array(
                'ticket' => Mage::helper('klevu_search/config')->getJsApiKey(),
                'noOfResults' => $noOfResults,
                'term' => $this->_query,
                'paginationStartsFrom' => $this->_getStartFrom($page),
                'klevuSort' => $this->_getSortOrder(),
                'enableFilters' => 'true',
                'filterResults' => $this->_getPreparedFilters(),
                'category' => $this->_klevu_type_of_records
            );
            $this->log(Zend_Log::DEBUG, sprintf("Starting search for term: %s", $this->_getQuery()->getQueryText()));
        }

        return $this->_klevu_parameters;
    }
    
    /**
     * Return the Klevu api search filters
     * @return array
     */
    public function getSearchTracking($noOfTrackingResults,$queryType) {

        $this->_klevu_tracking_parameters = array(
            'klevu_apiKey' => Mage::helper('klevu_search/config')->getJsApiKey(),
            'klevu_term' => $this->_query,
            'klevu_totalResults' => $noOfTrackingResults,
            'klevu_shopperIP' => Mage::helper('klevu_search')->getIp(),
            'klevu_typeOfQuery' => $queryType,
            'Klevu_typeOfRecord' => 'KLEVU_PRODUCT'
        );
        $this->log(Zend_Log::DEBUG, sprintf("Search tracking for term: %s", $this->_query));
        return $this->_klevu_tracking_parameters;
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

    public function getKlevuFilters() {
        $attributes = array();
        $filters = $this->getKlevuResponse()->getData('filters');

        // If there are no filters, return empty array.
        if (empty($filters)) {
            return array();
        }

        foreach($filters as $filter)
        {
            $key = (string) $filter['key'];
            $attributes[$key] = array('label' => (string) $filter['label']);
            $attributes[$key]['options'] = array();
            if($filter['options']) {
                foreach($filter['options'] as $option) {
                    $attributes[$key]['options'][] = array(
                        'label' => trim((string) $option['name']),
                        'count' => trim((string) $option['count']),
                        'selected' => trim((string) $option['selected'])
                    );
                }
            }
        }

        return $attributes;
    }

    protected function _beforeLoad() {
        if ($this->isExtensionConfigured()) {
            $this->setVisibility(array(
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG,
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH,
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH));
            $this->addAttributeToSelect('visibility');
        }

        return parent::_beforeLoad();
    }

    protected function _renderFilters()
    {
        if (!$this->isExtensionConfigured()) {
            return parent::_renderFilters();
        }

        // Do nothing. The results returned by the API are already filtered
        // and the collection is filtered to only include those results
        // in _loadEntities()

        return $this;
    }

    protected function _renderOrders()
    {
        if (!$this->isExtensionConfigured()) {
            return parent::_renderOrders();
        }

        // Do nothing. The results returned by the API are already in order
        // which is enforced in _loadEntities()

        return $this;
    }

    protected function _afterLoad()
    {
        parent::_afterLoad();

        if (!$this->isExtensionConfigured()) {
            return $this;
        }

        foreach ($this->_klevu_parent_child_ids as $item) {

        
            if ($item['parent_id'] > 0) {
                /** @var Mage_Catalog_Model_Product $parent */
                $parent = $this->_items[$item['parent_id']];
                
                /** @var Mage_Catalog_Model_Product $child */
                $child = '';
                if (isset($this->_items[$item['product_id']])) {
                 $child = $this->_items[$item['product_id']];
                }
                // Parent isn't visible. Unset both child and parent products and skip.
                if (!$parent || !$this->_isProductVisible($parent)) {
                    unset($this->_items[$item['parent_id']], $this->_items[$item['product_id']]);
                    continue;
                }

               if ($child) {
                    // Set children images on parent product
                    $image = $child->getData('image');
                    if ($child->getData('image') != 'no_selection' && !empty($image)) {
                        $parent->setData('image', $image);
                    }
                    
                    $small_image = $child->getData('small_image');
                    if ($child->getData('small_image') != 'no_selection' && !empty($small_image)) {
                        $parent->setData('small_image', $small_image);
                    }
                      
                    $thumbnail = $child->getData('thumbnail');  
                    if ($child->getData('thumbnail') != 'no_selection' && !empty($thumbnail)) {
                        $parent->setData('thumbnail', $thumbnail);
                    }
                }

                unset($this->_items[$item['product_id']]);
            }

            // If the child exists, but isn't visible unset the item from our collection.
            if (isset($this->_items[$item['product_id']]) && !$this->_isProductVisible($this->_items[$item['product_id']])) {
                unset($this->_items[$item['product_id']]);
            }
        }

        return $this;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    protected function _isProductVisible($product) {
        return in_array(
            $product->getData('visibility'),
            array(
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH,
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH
            )
        );
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
     * Return the current sort order, as used by Klevu.
     *
     * @return string
     */
    protected function _getSortOrder() {
        $order = $this->_getToolbar()->getCurrentOrder();
        $direction = $this->_getToolbar()->getCurrentDirection();

        switch ($order) {
            case "price":
                return ($direction == "desc") ? "htl" : "lth";
            case "name":
                return ($direction == "desc") ? "namedesc" : "nameasc";
            default:
                // Default to sorting by relevance
                return "rel";
        }
    }

    /**
     * Returns where Klevu should start pagination from, e.g. 0, 30 or 60 records (page 1, 2 and 3)
     *
     * @param null|int $current_page
     * @return int
     */
    protected function _getStartFrom($current_page = null) {
        if ($current_page == 1 || is_null($current_page)) {
            return 0;
        }
        return ($current_page - 1) * ($this->getPageSize());
    }

    /**
     * Overwriting the getSize method to use Klevu's result of total records.
     *
     * @return int
     */
    public function getSize() {
        if (!$this->isExtensionConfigured()) {
            return parent::getSize();
        }
        $response = $this->getKlevuResponse()->getData('meta');
        return (int) $response['totalResultsFound'];
    }

    /**
     * Get the current page size. If _pageSize is false, get the limit from the toolbar block.
     *
     * @return int|string
     */
    public function getPageSize() {
        if (!$this->isExtensionConfigured()) {
            return parent::getPageSize();
        }

        if(!$this->_pageSize) {
            return $this->_getToolbar()->getLimit();
        }
        return $this->_pageSize;
    }

    /**
     * Fetch the toolbar block
     *
     * @return Mage_Catalog_Block_Product_List_Toolbar
     */
    protected function _getToolbar() {
        /** @var Mage_Catalog_Block_Product_List $productListBlock */
        $productListBlock = Mage::getBlockSingleton('catalog/product_list');
        return $productListBlock->getToolbarBlock();
    }

    /**
     * Load entities records into items
     *
     * Removed page limiting SQL from this method to prevent issues with paging and Klevu.
     *
     * @throws Exception
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function _loadEntities($printQuery = false, $logQuery = false)
    {
        if (!$this->isExtensionConfigured()) {
            return parent::_loadEntities($printQuery, $logQuery);
        }

        // API results are already filtered, so include only the products
        // returned by the API in the collection
        $this->getSelect()->reset(Zend_Db_Select::WHERE);
        $this->addFieldToFilter('entity_id', array('in' => $this->_getProductIds()));

        // API results are ordered using the selected sort order, so enforce
        // the collection order to match the API results
        $this->getSelect()->reset(Zend_Db_Select::ORDER);
        $this->getSelect()->reset(Zend_Db_Select::LIMIT_OFFSET);
        if (count($this->_getProductIds())) {
            // Use "FIELD (column, 1[,2,3,4]) ASC" for ordering, where "1[,2,3,4]" is the list of IDs in the order required
            $this->getSelect()->order(sprintf('FIELD(`e`.`entity_id`, %s) ASC', implode(',', $this->_getProductIds())));
        }

        $this->printLogQuery($printQuery, $logQuery);

        try {
            /**
             * Prepare select query
             * @var string $query
             */
            if (is_callable(array($this, "_prepareSelect"))) {
                $query = $this->_prepareSelect($this->getSelect());
            } else {
                $query = $this->getSelect();
            }
            $rows = $this->_fetchAll($query);
        } catch (Exception $e) {
            Mage::printException($e, $query);
            $this->printLogQuery(true, true, $query);
            throw $e;
        }

        foreach ($rows as $v) {
            $object = $this->getNewEmptyItem()
                ->setData($v);
            $this->addItem($object);
            if (isset($this->_itemsById[$object->getId()])) {
                $this->_itemsById[$object->getId()][] = $object;
            } else {
                $this->_itemsById[$object->getId()] = array($object);
            }
        }

        return $this;
    }

    /**
     * Get the active filters, then prepare them for Klevu.
     *
     * @return string
     */
    protected function _getPreparedFilters() {
        $layer = Mage::getSingleton('catalogsearch/layer');
        $filters = $layer->getState()->getFilters();
        $prepared_filters = array();

        /** @var Mage_Catalog_Model_Layer_Filter_Item $filter */
        foreach ($filters as $filter) {
            $filter_type = $filter->getFilter()->getRequestVar();
            $label = Mage::helper('klevu_search')->santiseAttributeValue(strtolower($filter->getData('label')));

            switch($filter_type) {
                case "cat":
                    $prepared_filters['category'] = $label;
                    break;
                case "price":
                    $prepared_filters['klevu_price'] = implode(' - ', $filter->getFilter()->getData('interval'));
                    break;
                default:
                    $prepared_filters[$filter->getFilter()->getAttributeModel()->getAttributeCode()] = $label;
                    break;
            }
        }

        $this->log(Zend_Log::DEBUG, sprintf('Active Filters: %s', var_export($prepared_filters, true)));

        return implode(
            ';;',
            array_map(
                function($v, $k) {
                    return sprintf('%s:%s', $k, $v);
                },
                $prepared_filters,
                array_keys($prepared_filters)
            )
        );

    }

    protected function log($level, $message) {
        Mage::helper('klevu_search')->log($level, $message);
    }

    protected function isExtensionConfigured() {
        return Mage::helper('klevu_search/config')->isExtensionConfigured();
    }
}

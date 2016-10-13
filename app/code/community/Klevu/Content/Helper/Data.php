<?php
class Klevu_Content_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_klevu_Content_parameters;
    protected $_klevu_Content_response;
    protected $_klevu_Cms_Data;
    
    const XML_PATH_CMS_SYNC_ENABLED = "klevu_search/product_sync/enabledcms";
    const XML_PATH_EXCLUDED_CMS_PAGES = "klevu_search/cmscontent/excludecms";
    const XML_PATH_EXCLUDEDCMS_PAGES = "klevu_search/cmscontent/excludecms_pages";
    const XML_PATH_CMS_ENABLED_ON_FRONT = "klevu_search/cmscontent/enabledcmsfront";
    /**
     * Return the Klevu api content filters
     * @return array
     */
    public function getContentSearchFilters()
    {
        if (empty($this->_klevu_Content_parameters)) {
            $q = Mage::app()->getRequest()->getParam('q');
            $this->_klevu_Content_parameters = array(
                'ticket' => Mage::helper('klevu_search/config')->getJsApiKey() ,
                'noOfResults' => 1000,
                'term' => $q,
                'klevuSort' => 'rel',
                'paginationStartsFrom' => 0,
                'enableFilters' => 'true',
                'category' => 'KLEVU_CMS',
                'fl' => 'name,shortDesc,url',
                'klevuShowOutOfStockProducts' => 'true',
                'filterResults' => $this->_getPreparedFilters() ,
            );
            $this->log(Zend_Log::DEBUG, sprintf("Starting search for term: %s", $q));
        }
        return $this->_klevu_Content_parameters;
    }
    /**
     * Send the API Request and return the API Response.
     * @return Klevu_Search_Model_Api_Response
     */
    public function getKlevuResponse()
    {
        if (!$this->_klevu_Content_response) {
            $this->_klevu_Content_response = Mage::getModel('klevu_search/api_action_idsearch')->execute($this->getContentSearchFilters());
        }
        return $this->_klevu_Content_response;
    }
    
    /**
     * Return the Klevu api search filters
     * @return array
     */
    public function getContentSearchTracking($noOfTrackingResults,$queryType) {
        $q = Mage::app()->getRequest()->getParam('q');
        $this->_klevu_tracking_parameters = array(
            'klevu_apiKey' => Mage::helper('klevu_search/config')->getJsApiKey(),
            'klevu_term' => $q,
            'klevu_totalResults' => $noOfTrackingResults,
            'klevu_shopperIP' => Mage::helper('klevu_search')->getIp(),
            'klevu_typeOfQuery' => $queryType,
            'Klevu_typeOfRecord' => 'KLEVU_CMS'
        );
        $this->log(Zend_Log::DEBUG, sprintf("Content Search tracking for term: %s", $q));
        return $this->_klevu_tracking_parameters;
    }
    
    /**
     * This method executes the the Klevu API request if it has not already been called, and takes the result
     * with the result
     * We then add all these values to our class variable $_klevu_Cms_Data.
     *
     * @return array
     */
    Public function getCmsData()
    {
        if (empty($this->_klevu_Cms_Data)) {
            // If no results, return an empty array
            if (!$this->getKlevuResponse()->hasData('result')) {
                return array();
            }
            foreach($this->getKlevuResponse()->getData('result') as $key => $value) {
                $value["name"] = $value['name'];
                $value["url"] = $value["url"];
                if (!empty($value['shortDesc'])) {
                    $value["shortDesc"] = $value['shortDesc'];
                }
                $cms_data[] = $value;
            }
            $this->_klevu_Cms_Data = $cms_data;
            
            $response_meta = $this->getKlevuResponse()->getData('meta');
            Mage::getModel('klevu_search/api_action_searchtermtracking')->execute($this->getContentSearchTracking(count($this->_klevu_Cms_Data),$response_meta['typeOfQuery']));
            $this->log(Zend_Log::DEBUG, sprintf("Cms count returned: %s", count($this->_klevu_Cms_Data)));
        }
        return $this->_klevu_Cms_Data;
    }
    /**
     * Print Log in Klevu log file.
     *
     * @param int $level ,string $message
     *
     */
    protected function log($level, $message)
    {
        Mage::helper('klevu_search')->log($level, $message);
    }
    /**
     * Get excluded cms page for store.
     *
     * @param Mage_Core_Model_Store|int $store
     *
     * @return strings
     */
    public function getExcludedCmsPages($store = null)
    {
        return Mage::getStoreConfig(static ::XML_PATH_EXCLUDED_CMS_PAGES, $store);
    }
    
    /**
     * Get excluded cms page for store.
     *
     * @param Mage_Core_Model_Store|int $store
     *
     * @return strings
     */
    public function getExcludedPages($store = null)
    {
        $values = unserialize(Mage::getStoreConfig(static::XML_PATH_EXCLUDEDCMS_PAGES, $store));
        if (is_array($values)) {
            return $values;
        }
        return array();
    }
    
    
    /**
     * Get value of cms synchronize for the given store.
     *
     * @param Mage_Core_Model_Store|int $store
     *
     * @return bool
     */
    public function getCmsSyncEnabledFlag($store = null)
    {
        return intval(Mage::getStoreConfig(static ::XML_PATH_CMS_SYNC_ENABLED, $store));
    }
    /**
     * Check if Cms Sync is enabled for the given store.
     *
     * @param Mage_Core_Model_Store|int $store
     *
     * @return bool
     */
    public function isCmsSyncEnabled($store = null)
    {
        $flag = $this->getCmsSyncEnabledFlag($store);
        return in_array($flag, array(
            Klevu_Search_Model_System_Config_Source_Yesnoforced::YES,
        ));
    }
    /**
     * Get value of cms synchronize for the given store.
     *
     * @param Mage_Core_Model_Store|int $store
     *
     * @return bool
     */
    public function getCmsSyncEnabledOnFront($store = null)
    {
        return intval(Mage::getStoreConfig(static ::XML_PATH_CMS_ENABLED_ON_FRONT, $store));
    }
    /**
     * Check if Cms is enabled on frontend for the given store.
     *
     * @param Mage_Core_Model_Store|int $store
     *
     * @return bool
     */
    public function isCmsSyncEnabledOnFront($store = null)
    {
        $flag = $this->getCmsSyncEnabledOnFront($store);
        return in_array($flag, array(
            Klevu_Search_Model_System_Config_Source_Yesnoforced::YES,
        ));
    }
    /**
     * Get the type filters for Content from Klevu .
     *
     * @return array
     */
    public function getKlevuFilters()
    {
        $attributes = array();
        $filters = $this->getKlevuResponse()->getData('filters');
        // If there are no filters, return empty array.
        if (empty($filters)) {
            return array();
        }
        foreach($filters as $filter) {
            $key = (string)$filter['key'];
            $attributes[$key] = array(
                'label' => (string)$filter['label']
            );
            $attributes[$key]['options'] = array();
            if ($filter['options']) {
                foreach($filter['options'] as $option) {
                    $attributes[$key]['options'][] = array(
                        'label' => trim((string)$option['name']) ,
                        'count' => trim((string)$option['count']) ,
                        'selected' => trim((string)$option['selected'])
                    );
                }
            }
        }
        return $attributes;
    }
    /**
     * Get the active filters, then prepare them for Klevu.
     *
     * @return string
     */
    protected function _getPreparedFilters()
    {
        $prepared_filters = array();
        $filter_type = Mage::app()->getRequest()->getParam('cat');
        if (!empty($filter_type)) {
            switch ($filter_type) {
            case "cat":
                $prepared_filters['category'] = $filter_type;
                break;

            default:
                $prepared_filters['category'] = $filter_type;
                break;
            }
            $this->log(Zend_Log::DEBUG, sprintf('Active For Category Filters: %s', var_export($prepared_filters, true)));
            return implode(';;', array_map(function ($v, $k)
            {
                return sprintf('%s:%s', $k, $v);
            }
            , $prepared_filters, array_keys($prepared_filters)));
        }
    }
    
    /**
     * Return the Cms pages.
     *
     * @param int|Mage_Core_Model_Store $store
     *
     * @return array
     */
    public function getCmsPageMap($store = null) {
        $cmsmap = unserialize(Mage::getStoreConfig(static::XML_PATH_EXCLUDEDCMS_PAGES, $store));
        return (is_array($cmsmap)) ? $cmsmap : array();
    }
    
    public function setCmsPageMap($map, $store = null) {
        unset($map["__empty"]);
        Mage::helper("klevu_search/config")->setStoreConfig(static::XML_PATH_EXCLUDEDCMS_PAGES, serialize($map), $store);
        return $this;
    }
	
	/** 
     *  function starts here
     *  Remove html tags and replace it with space.
     *
     * @param $string
     *
     * @return $string
     */
 
	function ripTags($string) { 
	 
	    // ----- remove HTML TAGs ----- 
	    $string = preg_replace ('/<[^>]*>/', ' ', $string); 
	 
	    // ----- remove control characters ----- 
	    $string = str_replace("\r", '', $string);    
	    $string = str_replace("\n", ' ', $string);   
	    $string = str_replace("\t", ' ', $string);   
	 
	    // ----- remove multiple spaces ----- 
	    $string = trim(preg_replace('/ {2,}/', ' ', $string));
	 
	    return $string; 
	 
	}

}
<?php

class Klevu_Search_Helper_Config extends Mage_Core_Helper_Abstract {

    const XML_PATH_EXTENSION_ENABLED = "klevu_search/general/enabled";
    const XML_PATH_TEST_MODE         = "klevu_search/general/test_mode";
    const XML_PATH_TAX_ENABLED       = "klevu_search/tax_setting/enabled";
    const XML_PATH_SECUREURL_ENABLED = "klevu_search/secureurl_setting/enabled";
    const XML_PATH_LANDING_ENABLED   = "klevu_search/searchlanding/landenabled";
    const XML_PATH_JS_API_KEY        = "klevu_search/general/js_api_key";
    const XML_PATH_REST_API_KEY      = "klevu_search/general/rest_api_key";
    const XML_PATH_TEST_JS_API_KEY   = "klevu_search/general/test_js_api_key";
    const XML_PATH_TEST_REST_API_KEY = "klevu_search/general/test_rest_api_key";
    const XML_PATH_PRODUCT_SYNC_ENABLED   = "klevu_search/product_sync/enabled";
    const XML_PATH_PRODUCT_SYNC_FREQUENCY = "klevu_search/product_sync/frequency";
    const XML_PATH_PRODUCT_SYNC_LAST_RUN = "klevu_search/product_sync/last_run";
    const XML_PATH_ATTRIBUTES_ADDITIONAL  = "klevu_search/attributes/additional";
    const XML_PATH_ATTRIBUTES_AUTOMATIC  = "klevu_search/attributes/automatic";
    const XML_PATH_ATTRIBUTES_OTHER       = "klevu_search/attributes/other";
    const XML_PATH_ATTRIBUTES_BOOSTING       = "klevu_search/attribute_boost/boosting";
    const XML_PATH_ORDER_SYNC_ENABLED   = "klevu_search/order_sync/enabled";
    const XML_PATH_ORDER_SYNC_FREQUENCY = "klevu_search/order_sync/frequency";
    const XML_PATH_ORDER_SYNC_LAST_RUN = "klevu_search/order_sync/last_run";
    const XML_PATH_FORCE_LOG = "klevu_search/developer/force_log";
    const XML_PATH_ENABLE_EXTERNAL_CALL = "klevu_search/developer/enable_external_call";
    const XML_PATH_LOG_LEVEL = "klevu_search/developer/log_level";
    const XML_PATH_STORE_ID = "stores/%s/system/store/id";
    const XML_PATH_HOSTNAME = "klevu_search/general/hostname";
    const XML_PATH_RESTHOSTNAME = "klevu_search/general/rest_hostname";
    const XML_PATH_TEST_HOSTNAME = "klevu_search/general/test_hostname";
    const XML_PATH_CLOUD_SEARCH_URL = "klevu_search/general/cloud_search_url";
    const XML_PATH_TEST_CLOUD_SEARCH_URL = "klevu_search/general/test_cloud_search_url";
    const XML_PATH_ANALYTICS_URL = "klevu_search/general/analytics_url";
    const XML_PATH_TEST_ANALYTICS_URL = "klevu_search/general/test_analytics_url";
    const XML_PATH_JS_URL = "klevu_search/general/js_url";
    const XML_PATH_TEST_JS_URL = "klevu_search/general/test_js_url";
    const KLEVU_PRODUCT_FORCE_OLDERVERSION = 2;
    const XML_PATH_SYNC_OPTIONS = "klevu_search/product_sync/sync_options";
    const XML_PATH_UPGRADE_PREMIUM = "klevu_search/general/premium";
    const XML_PATH_RATING = "klevu_search/general/rating_flag";
    const XML_PATH_UPGRADE_FEATURES = "klevu_search/general/upgrade_features";
    const XML_PATH_UPGRADE_TIRES_URL = "klevu_search/general/tiers_url";

    const DATETIME_FORMAT = "Y-m-d H:i:s T";
    protected $_klevu_features_response;
    protected $_klevu_enabled_feature_response;

    /**
     * Set the Enable on Frontend flag in System Configuration for the given store.
     *
     * @param      $flag
     * @param Mage_Core_Model_Store|int|null $store Store to set the flag for. Defaults to current store.
     *
     * @return $this
     */
    public function setExtensionEnabledFlag($flag, $store = null) {
        $flag = ($flag) ? 1 : 0;
        $this->setStoreConfig(static::XML_PATH_EXTENSION_ENABLED, $flag, $store);
        return $this;
    }

    /**
     * Check if the Klevu_Search extension is enabled in the system configuration for the current store.
     *
     * @param $store_id
     *
     * @return bool
     */
    public function isExtensionEnabled($store_id = null) {
        return Mage::getStoreConfigFlag(static::XML_PATH_EXTENSION_ENABLED, $store_id);
    }
    
    /**
     * Check if the Tax is enabled in the system configuration for the current store.
     *
     * @param $store_id
     *
     * @return bool
     */
    public function isTaxEnabled($store_id = null) {
        $flag =  Mage::getStoreConfig(static::XML_PATH_TAX_ENABLED, $store_id);
        return in_array($flag, array(
                Klevu_Search_Model_System_Config_Source_Taxoptions::YES
        ));
    }
    
    /**
     * Check if the Secure url is enabled in the system configuration for the current store.
     *
     * @param $store_id
     *
     * @return bool
     */
    public function isSecureUrlEnabled($store_id = null) {
        return Mage::getStoreConfigFlag(static::XML_PATH_SECUREURL_ENABLED, $store_id);
    }
    /**
     * Check if the Landing is enabled in the system configuration for the current store.
     *
     * @param $store_id
     *
     * @return bool
     */
    public function isLandingEnabled($store = null) {
        return intval(Mage::getStoreConfig(static::XML_PATH_LANDING_ENABLED, $store));
    }

    /**
     * Set the Test Mode flag in System Configuration for the given store.
     *
     * @param      $flag
     * @param null $store Store to use. If not specified, uses the current store.
     *
     * @return $this
     */
    public function setTestModeEnabledFlag($flag, $store = null) {
        $flag = ($flag) ? 1 : 0;
        $this->setStoreConfig(static::XML_PATH_TEST_MODE, $flag, $store);
        return $this;
    }

    /**
     * Return the configuration flag for enabling test mode.
     *
     * @param Mage_Core_Model_Store|int $store
     *
     * @return bool
     */
    public function getTestModeEnabledFlag($store = null) {
        return Mage::getStoreConfigFlag(static::XML_PATH_TEST_MODE, $store);
    }
    
    /**
     * Set the Tax mode in System Configuration for the given store.
     *
     * @param      $flag
     * @param null $store Store to use. If not specified, uses the current store.
     *
     * @return $this
     */
    public function setTaxEnabledFlag($flag, $store = null) {
        //$flag = ($flag) ? 1 : 0;
        $this->setStoreConfig(static::XML_PATH_TAX_ENABLED, $flag, $store);
        return $this;
        
    }
    
    /**
     * Set the Secure Url mode in System Configuration for the given store.
     *
     * @param      $flag
     * @param null $store Store to use. If not specified, uses the current store.
     *
     * @return $this
     */
    public function setSecureUrlEnabledFlag($flag, $store = null) {
    
        $flag = ($flag) ? 1 : 0;
        $this->setStoreConfig(static::XML_PATH_SECUREURL_ENABLED, $flag, $store);
        return $this;
    }

    /**
     * Check if Test Mode is enabled for the given store.
     *
     * @param Mage_Core_Model_Store|int $store
     *
     * @return bool
     */
    public function isTestModeEnabled($store = null) {
        return $this->getTestModeEnabledFlag($store);
    }

    /**
     * Set the JS API key in System Configuration for the given store.
     *
     * @param string                    $key
     * @param Mage_Core_Model_Store|int $store     Store to use. If not specified, will use the current store.
     * @param bool                      $test_mode Set the key to be used in Test Mode.
     *
     * @return $this
     */
    public function setJsApiKey($key, $store = null, $test_mode = false) {
        $path = ($test_mode) ? static::XML_PATH_TEST_JS_API_KEY : static::XML_PATH_JS_API_KEY;
        $this->setStoreConfig($path, $key, $store);
        return $this;
    }

    /**
     * Return the JS API key configured for the specified store.
     *
     * @param Mage_Core_Model_Store|int $store
     *
     * @return string
     */
    public function getJsApiKey($store = null) {
        if ($this->isTestModeEnabled($store)) {
            return Mage::getStoreConfig(static::XML_PATH_TEST_JS_API_KEY, $store);
        } else {
            return Mage::getStoreConfig(static::XML_PATH_JS_API_KEY, $store);
        }
    }

    /**
     * Set the REST API key in System Configuration for the given store.
     *
     * @param string                    $key
     * @param Mage_Core_Model_Store|int $store     Store to use. If not specified, will use the current store.
     * @param bool                      $test_mode Set the key to be used in Test Mode.
     *
     * @return $this
     */
    public function setRestApiKey($key, $store = null, $test_mode = false) {
        $path = ($test_mode) ? static::XML_PATH_TEST_REST_API_KEY : static::XML_PATH_REST_API_KEY;
        $this->setStoreConfig($path, $key, $store);
        return $this;
    }

    /**
     * Return the REST API key configured for the specified store.
     *
     * @param Mage_Core_Model_Store|int $store
     *
     * @return mixed
     */
    public function getRestApiKey($store = null) {
        if ($this->isTestModeEnabled($store)) {
            return Mage::getStoreConfig(static::XML_PATH_TEST_REST_API_KEY, $store);
        } else {
            return Mage::getStoreConfig(static::XML_PATH_REST_API_KEY, $store);
        }
    }

    /**
     * Set the API Hostname value in System Configuration for a given store
     * @param $hostname
     * @param null $store
     * @param bool $test_mode
     * @return $this
     */
    public function setHostname($hostname, $store = null, $test_mode = false) {
        $path = ($test_mode) ? static::XML_PATH_TEST_HOSTNAME : static::XML_PATH_HOSTNAME;
        $this->setStoreConfig($path, $hostname, $store);
        return $this;
    }

    /**
     * Return the API Hostname configured, used for API requests, for a specified store
     * @param Mage_Core_Model_Store|int|null $store
     * @return string
     */
    public function getHostname($store = null) {
        // Store was provided, check for test mode before getting the configured hostname
        if($this->isTestModeEnabled($store)) {
            $hostname = Mage::getStoreConfig(static::XML_PATH_TEST_HOSTNAME, $store);
        } else {
            $hostname = Mage::getStoreConfig(static::XML_PATH_HOSTNAME, $store);
        }

        return ($hostname) ? $hostname : Klevu_Search_Helper_Api::ENDPOINT_DEFAULT_HOSTNAME;
    }
    
    /**
     * Return the API Rest Hostname configured, used for API requests, for a specified store
     * @param Mage_Core_Model_Store|int|null $store
     * @return string
     */
    public function getRestHostname($store = null) {
        // Store was provided, check for test mode before getting the configured hostname
        if($this->isTestModeEnabled($store)) {
            $hostname = Mage::getStoreConfig(static::XML_PATH_RESTHOSTNAME, $store);
        } else {
            $hostname = Mage::getStoreConfig(static::XML_PATH_RESTHOSTNAME, $store);
        }

        return ($hostname) ? $hostname : Klevu_Search_Helper_Api::ENDPOINT_DEFAULT_HOSTNAME;
    }
    
     /**
     * Set the Rest Hostname value in System Configuration for a given store
     * @param $url
     * @param null $store
     * @param bool $test_mode
     * @return $this
     */
    public function setRestHostname($url, $store = null, $test_mode = false) {
        $path = ($test_mode) ? static::XML_PATH_RESTHOSTNAME : static::XML_PATH_RESTHOSTNAME;
        $this->setStoreConfig($path, $url, $store);
        return $this;
    }

    /**
     * @param $url
     * @param null $store
     * @param bool $test_mode
     * @return $this
     */
    public function setCloudSearchUrl($url, $store = null, $test_mode = false) {
        $path = ($test_mode) ? static::XML_PATH_TEST_CLOUD_SEARCH_URL : static::XML_PATH_CLOUD_SEARCH_URL;
        $this->setStoreConfig($path, $url, $store);
        return $this;
    }

    /**
     * @param null $store
     * @return string
     */
    public function getCloudSearchUrl($store = null) {
        if($this->isTestModeEnabled($store)) {
            $url = Mage::getStoreConfig(static::XML_PATH_TEST_CLOUD_SEARCH_URL, $store);
        } else {
            $url = Mage::getStoreConfig(static::XML_PATH_CLOUD_SEARCH_URL, $store);
        }

        return ($url) ? $url : Klevu_Search_Helper_Api::ENDPOINT_DEFAULT_HOSTNAME;
    }

    /**
     * @param $url
     * @param null $store
     * @param bool $test_mode
     * @return $this
     */
    public function setAnalyticsUrl($url, $store = null, $test_mode = false) {
        $path = ($test_mode) ? static::XML_PATH_TEST_ANALYTICS_URL : static::XML_PATH_ANALYTICS_URL;
        $this->setStoreConfig($path, $url, $store);
        return $this;
    }
    
    /**
     * @param $url
     * @param null $store
     * @param bool $test_mode
     * @return $this
     */
    public function setTiresUrl($url, $store = null, $test_mode = false) {
        $path = static::XML_PATH_UPGRADE_TIRES_URL;
        $this->setStoreConfig($path, $url, $store);
        return $this;
    }
    
    /**
     * @param null $store
     * @return string
     */
    public function getTiresUrl($store = null) {
        $url = Mage::getStoreConfig(static::XML_PATH_UPGRADE_TIRES_URL,$store);
        return ($url) ? $url : Klevu_Search_Helper_Api::ENDPOINT_DEFAULT_HOSTNAME;
    }

    /**
     * @param null $store
     * @return string
     */
    public function getAnalyticsUrl($store = null) {
        if($this->isTestModeEnabled($store)) {
            $url = Mage::getStoreConfig(static::XML_PATH_TEST_ANALYTICS_URL);
        } else {
            $url = Mage::getStoreConfig(static::XML_PATH_ANALYTICS_URL);
        }

        return ($url) ? $url : Klevu_Search_Helper_Api::ENDPOINT_DEFAULT_HOSTNAME;
    }

    /**
     * @param $url
     * @param null $store
     * @param bool $test_mode
     * @return $this
     */
    public function setJsUrl($url, $store = null, $test_mode = false) {
        $path = ($test_mode) ? static::XML_PATH_TEST_JS_URL : static::XML_PATH_JS_URL;
        $this->setStoreConfig($path, $url, $store);
        return $this;
    }

    /**
     * @param null $store
     * @return string
     */
    public function getJsUrl($store = null) {
        if($this->isTestModeEnabled($store)) {
            $url = Mage::getStoreConfig(static::XML_PATH_TEST_JS_URL);
        } else {
            $url = Mage::getStoreConfig(static::XML_PATH_JS_URL);
        }

        return ($url) ? $url : Klevu_Search_Helper_Api::ENDPOINT_DEFAULT_HOSTNAME;
    }

    /**
     * Check if the Klevu Search extension is configured for the given store.
     *
     * @param null $store_id
     *
     * @return bool
     */
    public function isExtensionConfigured($store_id = null) {
        $js_api_key = $this->getJsApiKey($store_id);
        $rest_api_key = $this->getRestApiKey($store_id);

        return (
            $this->isExtensionEnabled($store_id)
            && !empty($js_api_key)
            && !empty($rest_api_key)
        );
    }

    /**
     * Return the system configuration setting for enabling Product Sync for the specified store.
     * The returned value can have one of three possible meanings: Yes, No and Forced. The
     * values mapping to these meanings are available as constants on
     * Klevu_Search_Model_System_Config_Source_Yesnoforced.
     *
     * @param $store_id
     *
     * @return int
     */
    public function getProductSyncEnabledFlag($store_id = null) {
        return intval(Mage::getStoreConfig(static::XML_PATH_PRODUCT_SYNC_ENABLED, $store_id));
    }

    /**
     * Check if Product Sync is enabled for the specified store and domain.
     *
     * @param $store_id
     *
     * @return bool
     */
    public function isProductSyncEnabled($store_id = null) {

        $flag = $this->getProductSyncEnabledFlag($store_id);

        // static::KLEVU_PRODUCT_FORCE_OLDERVERSION for handling of older version of klevu 
        //if (Mage::helper("klevu_search")->isProductionDomain(Mage::getBaseUrl())) {
            return in_array($flag, array(
                Klevu_Search_Model_System_Config_Source_Yesnoforced::YES,
                static::KLEVU_PRODUCT_FORCE_OLDERVERSION
            ));
        //} else {
        //    return $flag === Klevu_Search_Model_System_Config_Source_Yesnoforced::FORCED;
        //}
    }

    /**
     * Return the configured frequency expression for Product Sync.
     *
     * @return string
     */
    public function getProductSyncFrequency() {
        return Mage::getStoreConfig(static::XML_PATH_PRODUCT_SYNC_FREQUENCY);
    }

    /**
     * Set the last Product Sync run time in System Configuration for the given store.
     *
     * @param DateTime|string                $datetime If string is passed, it will be converted to DateTime.
     * @param Mage_Core_Model_Store|int|null $store
     *
     * @return $this
     */
    public function setLastProductSyncRun($datetime = "now", $store = null) {
        if (!$datetime instanceof DateTime) {
            $datetime = new DateTime($datetime);
        }

        $this->setStoreConfig(static::XML_PATH_PRODUCT_SYNC_LAST_RUN, $datetime->format(static::DATETIME_FORMAT), $store);

        return $this;
    }

    /**
     * Check if Product Sync has ever run for the given store.
     *
     * @param Mage_Core_Model_Store|int|null $store
     *
     * @return bool
     */
    public function hasProductSyncRun($store = null) {
        $config = Mage::getConfig();

        if (!$config->getNode(static::XML_PATH_PRODUCT_SYNC_LAST_RUN, "store", Mage::app()->getStore($store)->getId())) {
            return false;
        }

        return true;
    }

    public function setAdditionalAttributesMap($map, $store = null) {
        unset($map["__empty"]);
        $this->setStoreConfig(static::XML_PATH_ATTRIBUTES_ADDITIONAL, serialize($map), $store);
        return $this;
    }

    /**
     * Return the map of additional Klevu attributes to Magento attributes.
     *
     * @param int|Mage_Core_Model_Store $store
     *
     * @return array
     */
    public function getAdditionalAttributesMap($store = null) {
        $map = unserialize(Mage::getStoreConfig(static::XML_PATH_ATTRIBUTES_ADDITIONAL, $store));

        return (is_array($map)) ? $map : array();
    }

    /**
     * Set the automatically mapped attributes
     * @param array $map
     * @param int|Mage_Core_Model_Store $store
     * @return $this
     */
    public function setAutomaticAttributesMap($map, $store = null) {
        unset($map["__empty"]);
        $this->setStoreConfig(static::XML_PATH_ATTRIBUTES_AUTOMATIC, serialize($map), $store);
        return $this;
    }

    /**
     * Returns the automatically mapped attributes
     * @param int|Mage_Core_Model_Store $store
     * @return array
     */
    public function getAutomaticAttributesMap($store = null) {
        $map = unserialize(Mage::getStoreConfig(static::XML_PATH_ATTRIBUTES_AUTOMATIC, $store));

        return (is_array($map)) ? $map : array();
    }

    /**
     * Return the System Configuration setting for enabling Order Sync for the given store.
     * The returned value can have one of three possible meanings: Yes, No and Forced. The
     * values mapping to these meanings are available as constants on
     * Klevu_Search_Model_System_Config_Source_Yesnoforced.
     *
     * @param Mage_Core_Model_Store|int $store
     *
     * @return int
     */
    public function getOrderSyncEnabledFlag($store = null) {
        return intval(Mage::getStoreConfig(static::XML_PATH_ORDER_SYNC_ENABLED, $store));
    }

    /**
     * Check if Order Sync is enabled for the given store on the current domain.
     *
     * @param Mage_Core_Model_Store|int $store
     *
     * @return bool
     */
    public function isOrderSyncEnabled($store = null) {
        $flag = $this->getOrderSyncEnabledFlag($store);
        // static::KLEVU_PRODUCT_FORCE_OLDERVERSION for handling of older version of klevu
        //if (Mage::helper("klevu_search")->isProductionDomain(Mage::getBaseUrl())) {
            return in_array($flag, array(
                Klevu_Search_Model_System_Config_Source_Yesnoforced::YES,
                static::KLEVU_PRODUCT_FORCE_OLDERVERSION
            ));
        //} else {
            //return $flag === Klevu_Search_Model_System_Config_Source_Yesnoforced::FORCED;
        //}
    }

    /**
     * Return the configured frequency expression for Order Sync.
     *
     * @return string
     */
    public function getOrderSyncFrequency() {
        return Mage::getStoreConfig(static::XML_PATH_ORDER_SYNC_FREQUENCY);
    }

    /**
     * Set the last Order Sync run time in System Configuration.
     *
     * @param DateTime|string $datetime If string is passed, it will be converted to DateTime.
     *
     * @return $this
     */
    public function setLastOrderSyncRun($datetime = "now") {
        if (!$datetime instanceof DateTime) {
            $datetime = new DateTime($datetime);
        }

        $this->setGlobalConfig(static::XML_PATH_ORDER_SYNC_LAST_RUN, $datetime->format(static::DATETIME_FORMAT));

        return $this;
    }

    /**
     * Check if default Magento log settings should be overridden to force logging for this module.
     *
     * @return bool
     */
    public function isLoggingForced() {
        return Mage::getStoreConfigFlag(static::XML_PATH_FORCE_LOG);
    }
    
    /**
     * Check if KLEVU can sync data by exteranl url.
     *
     * @return bool
     */
    public function isExternalCallEnabled() {
        return Mage::getStoreConfigFlag(static::XML_PATH_ENABLE_EXTERNAL_CALL);
    }


    /**
     * Return the minimum log level configured. Default to Zend_Log::WARN.
     *
     * @return int
     */
    public function getLogLevel() {
        $log_level = Mage::getStoreConfig(static::XML_PATH_LOG_LEVEL);

        return ($log_level !== null) ? intval($log_level) : Zend_Log::INFO;
    }

    /**
     * Return an multi-dimensional array of magento and klevu attributes that are mapped by default.
     * @return array
     */
    public function getDefaultMappedAttributes() {
        return array(
            "magento_attribute" => array(
                "name",
                "sku",
                "image",
                "description",
                "short_description",
                "price",
                "price",
                "tax_class_id",
                "weight",
                "rating"),
            "klevu_attribute" => array(
                "name",
                "sku",
                "image",
                "desc",
                "shortDesc",
                "price",
                "salePrice",
                "salePrice",
                "weight",
                "rating"
            )
        );
    }

    /**
     * Returns array of other attributes map from store configuration.
     *
     * @param Mage_Core_Model_Store|int|null $store
     * @return array
     */
    public function getOtherAttributesToIndex($store = null) {
        if (Mage::getStoreConfig(static::XML_PATH_ATTRIBUTES_OTHER, $store)) {
            return explode(",", Mage::getStoreConfig(static::XML_PATH_ATTRIBUTES_OTHER, $store));
        }

        return array();
    }

    /**
     * Return the boosting attribute defined in store configuration.
     *
     * @param Mage_Core_Model_Store|int|null $store
     * @return array
     */
    public function getBoostingAttribute($store = null) {
        return Mage::getStoreConfig(static::XML_PATH_ATTRIBUTES_BOOSTING, $store);
    }

    /**
     * Set the global scope System Configuration value for the given key.
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    protected function setGlobalConfig($key, $value) {
        Mage::getConfig()
            ->saveConfig($key, $value, "default")
            ->reinit();

        return $this;
    }

    /**
     * Set the store scope System Configuration value for the given key.
     *
     * @param string                         $key
     * @param string                         $value
     * @param Mage_Core_Model_Store|int|null $store If not given, current store will be used.
     *
     * @return $this
     */
    public function setStoreConfig($key, $value, $store = null) {
        $config = Mage::getConfig();

        $store_code = Mage::app()->getStore($store)->getCode();
        $scope_id = $config->getNode(sprintf(static::XML_PATH_STORE_ID, $store_code));
        if ($scope_id !== null) {
            $scope_id = (int) $scope_id;

            $config->saveConfig($key, $value, "stores", $scope_id);

            $config->reinit();
        }

        return $this;
    }
    
    /**
     * Return the configuration flag for sync options.
     *
     *
     * @return int
     */
    public function getSyncOptionsFlag() {
        return Mage::getStoreConfig(static::XML_PATH_SYNC_OPTIONS);
    }
    
    /**
     * save sync option value
     *
     * @param string $value
     *
     * @return
     */
    public function saveSyncOptions($value) {
        $this->setGlobalConfig(static::XML_PATH_SYNC_OPTIONS, $value);
        return $this;
    }
    
    /**
     * save upgrade button value
     *
     * @param string $value
     *
     * @return
     */
    public function saveUpgradePremium($value) {
        $this->setGlobalConfig(static::XML_PATH_UPGRADE_PREMIUM, $value);
        return $this;
    }
    
    /**
     * save upgrade rating value
     *
     * @param string $value
     *
     * @return
     */
    public function saveRatingUpgradeFlag($value) {
        $this->setGlobalConfig(static::XML_PATH_RATING, $value);
        return $this;
    }
    
    /**
     * get upgrade rating value
     *
     * @return int 
     */
    public function getRatingUpgradeFlag() {
        return Mage::getStoreConfig(static::XML_PATH_RATING);
    }
    
    /**
     * get feature update
     *
     * @return bool 
     */
    public function getFeaturesUpdate($elemnetID) {
        try {
            if (!$this->_klevu_features_response) {
                $this->_klevu_features_response = Mage::getModel("klevu_search/product_sync")->getFeatures();
            }
            $features = $this->_klevu_features_response;
            if(!empty($features) && !empty($features['disabled'])) {
                $checkStr = explode("_",$elemnetID);
                $disable_features =  explode(",",$features['disabled']);
                $code = Mage::app()->getRequest()->getParam('store');// store level
                $store = Mage::getModel('core/store')->load($code);
                if(in_array("preserves_layout", $disable_features) && Mage::app()->getRequest()->getParam('section')=="klevu_search") {
                    // when some upgrade plugin if default value set to 1 means preserve layout
                    // then convert to klevu template layout
                    if(Mage::getStoreConfig(Klevu_Search_Helper_Config::XML_PATH_LANDING_ENABLED,$store) == 1){
                        $this->setStoreConfig(Klevu_Search_Helper_Config::XML_PATH_LANDING_ENABLED,2,$store);
                    }
                }
                if (in_array($checkStr[count($checkStr)-1], $disable_features)  && Mage::app()->getRequest()->getParam('section')=="klevu_search") {
                        $check = $checkStr[count($checkStr)-1];
                        if(!empty($check)) {
                            $configs = Mage::getModel('core/config_data')->getCollection()
                            ->addFieldToFilter('path', array("like" => '%/'.$check.'%'))->load();
                            $data = $configs->getData();
                            if(!empty($data)) {
                                $this->setStoreConfig($data[0]['path'],0,$store);
                            }
                            return $features;
                        }
                }
      
            }                
        } catch(Exception $e) {
                Mage::helper('klevu_search')->log(Zend_Log::CRIT, sprintf("Error occured while getting features based on account %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
        }
        return;
    }
    
    
    public function  executeFeatures($restApi,$store) {
        if(!$this->_klevu_enabled_feature_response) {
            $param =  array("restApiKey" => $restApi,"store" => $store->getId());
            $features_request = Mage::getModel("klevu_search/api_action_features")->execute($param);
            if($features_request->isSuccessful() === true) {
                $this->_klevu_enabled_feature_response = $features_request->getData();
                $this->saveUpgradeFetaures(serialize($this->_klevu_enabled_feature_response),$store);
            } else {
                if(!empty($restApi)) {
                    $this->_klevu_enabled_feature_response = unserialize(Mage::getStoreConfig(static::XML_PATH_UPGRADE_FEATURES, $store));
                }
                Mage::helper('klevu_search')->log(Zend_Log::INFO,sprintf("failed to fetch feature details (%s)",$features_request->getMessage()));
            }
        }  
        return $this->_klevu_enabled_feature_response;        
    }
    
    public function saveUpgradeFetaures($value,$store=null) {
        $this->setStoreConfig(static::XML_PATH_UPGRADE_FEATURES,$value,$store);
    }
    
}

<?php

class Klevu_Search_Helper_Data extends Mage_Core_Helper_Abstract {

    const LOG_FILE = "Klevu_Search.log";

    const ID_SEPARATOR = "-";

    const SANITISE_STRING = "/:|,|;/";

    /**
     * Given a locale code, extract the language code from it
     * e.g. en_GB => en, fr_FR => fr
     *
     * @param string $locale
     */
    function getLanguageFromLocale($locale) {
        if (strlen($locale) == 5 && strpos($locale, "_") === 2) {
            return substr($locale, 0, 2);
        }

        return $locale;
    }

    /**
     * Return the language code for the given store.
     *
     * @param int|Mage_Core_Model_Store $store
     *
     * @return string
     */
    function getStoreLanguage($store = null) {
        if ($store = Mage::app()->getStore($store)) {
            return $this->getLanguageFromLocale($store->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE));
        }
    }
	
	
	/**
     * Return the store timezone for the given store.
     *
     * @param int|Mage_Core_Model_Store $store
     *
     * @return string
     */
    function getStoreTimeZone($store = null) {
        if ($store = Mage::app()->getStore($store)) {
            return $store->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
        }
    }
	

    /**
     * Check if the given domain is considered to be a valid domain for a production environment.
     *
     * @param $domain
     *
     * @return bool
     */
    public function isProductionDomain($domain) {
        return preg_match("/\b(staging|dev|local)\b/", $domain) == 0;
    }

    /**
     * Generate a Klevu product ID for the given product.
     *
     * @param int      $product_id Magento ID of the product to generate a Klevu ID for.
     * @param null|int $parent_id  Optional Magento ID of the parent product.
     *
     * @return string
     */
    public function getKlevuProductId($product_id, $parent_id = 0) {
        if ($parent_id != 0) {
            $parent_id .= static::ID_SEPARATOR;
        } else {
            $parent_id = "";
        }

        return sprintf("%s%s", $parent_id, $product_id);
    }

    /**
     * Convert a Klevu product ID back into a Magento product ID. Returns an
     * array with "product_id" element for the product ID and a "parent_id"
     * element for the parent product ID or 0 if the Klevu product doesn't have
     * a parent.
     *
     * @param $klevu_id
     *
     * @return array
     */
    public function getMagentoProductId($klevu_id) {
        $parts = explode(static::ID_SEPARATOR, $klevu_id, 2);

        if (count($parts) > 1) {
            return array('product_id' => $parts[1], 'parent_id' => $parts[0]);
        } else {
            return array('product_id' => $parts[0], 'parent_id' => "0");
        }
    }

    /**
     * Format bytes into a human readable representation, e.g.
     * 6815744 => 6.5M
     *
     * @param     $bytes
     * @param int $precision
     *
     * @return string
     */
    public function bytesToHumanReadable($bytes, $precision = 2) {
        $suffixes = array("", "k", "M", "G", "T", "P");
        $base = log($bytes) / log(1024);
        return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
    }

    /**
     * Convert human readable formatting of bytes to bytes, e.g.
     * 6.5M => 6815744
     *
     * @param $string
     *
     * @return int
     */
    public function humanReadableToBytes($string) {
        $suffix = strtolower(substr($string, -1));
        $result = substr($string, 0, -1);

        switch ($suffix) {
            case 'g': // G is the max unit as of PHP 5.5.12
                $result *= 1024;
            case 'm':
                $result *= 1024;
            case 'k':
                $result *= 1024;
                break;
            default:
                $result = $string;
        }

        return ceil($result);
    }

    /**
     * Return the configuration data for a "Sync All Products" button displayed
     * on the Manage Products page in the backend.
     *
     * @return array
     */
    public function getSyncAllButtonData() {
        return array(
            'label'   => $this->__("Sync All Products to Klevu"),
            'onclick' => sprintf("setLocation('%s')", Mage::getModel('adminhtml/url')->getUrl("adminhtml/klevu_search/sync_all"))
        );
    }

    /**
     * Write a log message to the Klevu_Search log file.
     *
     * @param int    $level
     * @param string $message
     */
    public function log($level, $message) {
        $config = Mage::helper("klevu_search/config");

        if ($level <= $config->getLogLevel()) {
            Mage::log($message, $level, static::LOG_FILE, $config->isLoggingForced());
        }
    }

    /**
     * Remove the characters used to organise the other attribute values from the
     * passed in string.
     *
     * @param string $value
     * @return string
     */
    public function santiseAttributeValue($value) {
        if (is_array($value)) {
            $sanitised_array = array();
            foreach($value as $item) {
                $sanitised_array[] = preg_replace(self::SANITISE_STRING, " ", $item);
            }
            return $sanitised_array;
        }
        return preg_replace(self::SANITISE_STRING, " ", $value);
    }

    /**
     * Return whether or not the current page is CatalogSearch.
     * @return bool
     */
    public function isCatalogSearch() {
        return in_array('catalogsearch_result_index', Mage::app()->getLayout()->getUpdate()->getHandles());
    }
    /**
     Generate a Klevu product sku with parent product.
     *
     * @param string      $product_sku Magento Sku of the product to generate a Klevu sku for.
     * @param null $parent_sku  Optional Magento Parent Sku of the parent product.
     *
     * @return string
     */
    public function getKlevuProductSku($product_sku, $parent_sku = "") {
        if (!empty($parent_sku)) {
            $parent_sku .= static::ID_SEPARATOR;
        } else {
            $parent_sku = "";
        }
        return sprintf("%s%s", $parent_sku, $product_sku);
    }
    
    /**
     Get Min price for group product.
     *
     * @param object $product.
     *
     * @return
     */    
    public function getGroupProductMinPrice($product,$store){
        try {
            $groupProductIds = $product->getTypeInstance()->getChildrenIds($product->getId());
            $config = Mage::helper('klevu_search/config');
            $groupPrices = array();
            foreach ($groupProductIds as $ids) {
                foreach ($ids as $id) {
                    $groupProduct = Mage::getModel('catalog/product')->load($id);
					$stockItem = $groupProduct->getStockItem();
					if($stockItem->getIsInStock())
					{
						if($config->isTaxEnabled($store->getId())) {
							$groupPrices[] = Mage::helper("tax")->getPrice($groupProduct, $groupProduct->getFinalPrice(), true, null, null, null, $store,false);
						} else {
							$groupPrices[] = $groupProduct->getFinalPrice();
						}
					}
                }
            }
            asort($groupPrices);
            $product->setFinalPrice(array_shift($groupPrices));
        } catch(Exception $e) {
            Mage::helper('klevu_search')->log(Zend_Log::WARN, sprintf("Unable to get group price for product id %s",$product->getId()));
        }            
    }
    
    /**
     * Get Min price for group product.
     *
     * @param object $product.
     *
     * @return
     */    
    public function getBundleProductPrices($item,$store){
        try {
            $config = Mage::helper('klevu_search/config');
            if($config->isTaxEnabled($store->getId())) {
                if (version_compare(Mage::getVersion(), "1.6.0.0", "<")) {
                    return $item->getPriceModel()->getPricesDependingOnTax($item,null,true);
                } else {
                    return $item->getPriceModel()->getTotalPrices($item, null, true, false);
                }
            } else {
                if (version_compare(Mage::getVersion(), "1.6.0.0", "<")) {
                    return $item->getPriceModel()->getPricesDependingOnTax($item,null,null);
                } else {
                    return $item->getPriceModel()->getTotalPrices($item, null, null, false);
                }
            }
        } catch(Exception $e) {
            Mage::helper('klevu_search')->log(Zend_Log::WARN, sprintf("Unable to get get group price for product id %s",$product->getId()));
        }
    }
    
    
    
    /**
     Get Original price for group product.
     *
     * @param object $product.
     *
     * @return
     */    
    public function getGroupProductOriginalPrice($product,$store){
        try {
            $groupProductIds = $product->getTypeInstance()->getChildrenIds($product->getId());
            $config = Mage::helper('klevu_search/config');
            $groupPrices = array();
            foreach ($groupProductIds as $ids) {
                foreach ($ids as $id) {
                    $groupProduct = Mage::getModel('catalog/product')->load($id);
					$stockItem = $groupProduct->getStockItem();
					if($stockItem->getIsInStock())
					{
						if($config->isTaxEnabled($store->getId())) {
							$groupPrices[] = Mage::helper("tax")->getPrice($groupProduct, $groupProduct->getPrice(), true, null, null, null, $store,false);
						} else {
							$groupPrices[] = $groupProduct->getPrice();
						}
					}
                }
            }
            asort($groupPrices);
            $product->setPrice(array_shift($groupPrices));
        } catch(Exception $e) {
            Mage::helper('klevu_search')->log(Zend_Log::WARN, sprintf("Unable to get original group price for product id %s",$product->getId()));
        }            
    }
    
    
    /**
     * Get the is active attribute id
     *
     * @return string
     */
    public function getIsActiveAttributeId(){
        $entity_type = Mage::getSingleton("eav/entity_type")->loadByCode("catalog_category");
        $entity_typeid = $entity_type->getId();
        $attributecollection = Mage::getModel("eav/entity_attribute")->getCollection()->addFieldToFilter("entity_type_id", $entity_typeid)->addFieldToFilter("attribute_code", "is_active");
        $attribute = $attributecollection->getFirstItem();
        return $attribute->getAttributeId();
    }
	
	
	/**
     * Get the is exclude attribute id
     *
     * @return string
     */
    public function getIsExcludeAttributeId(){
        $entity_type = Mage::getSingleton("eav/entity_type")->loadByCode("catalog_category");
        $entity_typeid = $entity_type->getId();
        $attributecollection = Mage::getModel("eav/entity_attribute")->getCollection()->addFieldToFilter("entity_type_id", $entity_typeid)->addFieldToFilter("attribute_code", "exclude_in_search");
        $attribute = $attributecollection->getFirstItem();
        return $attribute->getAttributeId();
    }
    
    /**
     * Get the is visibility attribute id
     *
     * @return string
     */
    public function getVisibilityAttributeId(){
        $entity_type = Mage::getSingleton("eav/entity_type")->loadByCode("catalog_product");
        $entity_typeid = $entity_type->getId();
        $attributecollection = Mage::getModel("eav/entity_attribute")->getCollection()->addFieldToFilter("entity_type_id", $entity_typeid)->addFieldToFilter("attribute_code", "visibility");
        $attribute = $attributecollection->getFirstItem();
        return $attribute->getAttributeId();
    }
	
    /**
     * Get the client ip address
     *
     * @return string
     */
	public function getIp() {
		$ip = '';
		if (!empty($_SERVER['HTTP_CLIENT_IP']))
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if(!empty($_SERVER['HTTP_X_FORWARDED']))
			$ip = $_SERVER['HTTP_X_FORWARDED'];
		else if(!empty($_SERVER['HTTP_FORWARDED_FOR']))
			$ip = $_SERVER['HTTP_FORWARDED_FOR'];
		else if(!empty($_SERVER['HTTP_FORWARDED']))
			$ip = $_SERVER['HTTP_FORWARDED'];
		else if(!empty($_SERVER['REMOTE_ADDR']))
			$ip = $_SERVER['REMOTE_ADDR'];
		else
			$ip = 'UNKNOWN';
	 
		return $ip;
    }
	
	/**
     * Get the currecy switcher data
     *
     * @return string
     */
	public function getCurrencyData() {
	    $baseCurrencyCode = Mage::app()->getBaseCurrencyCode();
		$currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
		if($baseCurrencyCode != $currentCurrencyCode){
	        $availableCurrencies = Mage::app()->getStore()->getAvailableCurrencyCodes();
            $currencyRates = Mage::getModel('directory/currency')->getCurrencyRates($baseCurrencyCode, array_values($availableCurrencies));
	        if(count($availableCurrencies) > 1) { 
                foreach($currencyRates as $key => &$value){
					$Symbol = Mage::app()->getLocale()->currency($key)->getSymbol() ? Mage::app()->getLocale()->currency($key)->getSymbol() : Mage::app()->getLocale()->currency($key)->getShortName();
			        $value = sprintf("'%s':'%s:%s'", $key,$value,$Symbol);
		        }
		        $currency = implode(",",$currencyRates);
			    return $currency;
		    }
	    }
	}
	
	/**
     * Get total product count which have visibility catalog search
	 * /Not visible individual/search/enable in Magento
     *
     * @return count
     */
	public function getTotalProductCount() {
		$stores = Mage::app()->getStores();
		foreach ($stores as $store) {
			$products = Mage::getResourceModel('catalog/product_collection')
			->setStore($store->getId())
			->addStoreFilter($store->getId())
			->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
			->addAttributeToFilter('visibility', array('in' => array(
				Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH,
				Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
				Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
			)));
			$count[] = $products->getSize();
	    }
		return min($count);
    }
	
	/**
     * Get Klevu plans
	 * /Not visible individual/search/enable in Magento
     *
     * @return count
     */
	public function getPlans() {
		$response = Mage::getModel('klevu_search/api_action_getplans')->execute(array("store"=>"magento"));
		if ($response->isSuccessful()) {
		    $plans = $response->getData();
			return $plans['plans']['plan'];
		} else {
			return;
		}
    }
	
}

<?php

/**
 * Class Klevu_Search_Model_Observer
 *
 * @method setIsProductSyncScheduled($flag)
 * @method bool getIsProductSyncScheduled()
 */
class Klevu_Search_Model_Observer extends Varien_Object
{

    /**
     * Schedule a Product Sync to run immediately.
     *
     * @param Varien_Event_Observer $observer
     */
    public function scheduleProductSync(Varien_Event_Observer $observer) 
    {
        if (!$this->getIsProductSyncScheduled()) {
            if(Mage::helper("klevu_search/config")->isExternalCronEnabled()) {
                Mage::getModel("klevu_search/product_sync")->schedule();
            }

            $this->setIsProductSyncScheduled(true);
        }
    }
    
    
    /**
     * Schedule an Order Sync to run immediately. If the observed event
     * contains an order, add it to the sync queue before scheduling.
     *
     * @param Varien_Event_Observer $observer
     */
    public function scheduleOrderSync(Varien_Event_Observer $observer) 
    {
        try {
            $store = Mage::app()->getStore($observer->getEvent()->getStore());
            if(Mage::helper("klevu_search/config")->isOrderSyncEnabled($store->getId())) {
                $model = Mage::getModel("klevu_search/order_sync");
                $order = $observer->getEvent()->getOrder();
                if ($order) {
                $model->addOrderToQueue($order);
                }

                $model->schedule();
            }
        } catch(Exception $e) {
            // Catch the exception that was thrown, log it.
            Mage::helper('klevu_search')->log(Zend_Log::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
        }
    }

    /**
     * When products are updated in bulk, update products so that they will be synced.
     * @param Varien_Event_Observer $observer
     */
    public function setProductsToSync(Varien_Event_Observer $observer) 
    {
        $product_ids = $observer->getData('product_ids');

        if(empty($product_ids)) {
            return;
        }

        $product_ids = implode(',', $product_ids);
        $where = sprintf("product_id IN(%s) OR parent_id IN(%s)", $product_ids, $product_ids);
        $resource = Mage::getSingleton('core/resource');
        $resource->getConnection('core_write')
            ->update(
                $resource->getTableName('klevu_search/product_sync'),
                array('last_synced_at' => '0'),
                $where
            );
    }

    /**
     * Mark all of the products for update and then schedule a sync
     * to run immediately.
     *
     * @param Varien_Event_Observer $observer
     */
    public function syncAllProducts(Varien_Event_Observer $observer) 
    {
        $store = null;
        $sync = Mage::getModel("klevu_search/product_sync");

        $attribute = $observer->getEvent()->getAttribute();
        if ($attribute instanceof Mage_Catalog_Model_Resource_Eav_Attribute) {
            // On attribute change, sync only if the attribute was added
            // or removed from layered navigation
            if ($attribute->getOrigData("is_filterable_in_search") == $attribute->getData("is_filterable_in_search")) {
                return;
            }
        }

        if ($observer->getEvent()->getStore()) {
            // Only sync products for a specific store if the event was fired in that store
            $store = Mage::app()->getStore($observer->getEvent()->getStore());
        }

        $sync->markAllProductsForUpdate($store);

        if (!$this->getIsProductSyncScheduled()) {
            if(Mage::helper("klevu_search/config")->isExternalCronEnabled()) {
                $sync->schedule();
            }

            $this->setIsProductSyncScheduled(true);
        }
    }
    /**
     * When product image updated from admin this will generate the image thumb.
     * @param Varien_Event_Observer $observer
     */
    public function createThumb(Varien_Event_Observer $observer) 
    {
    
        try {
            if($observer->getEvent()->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE){        
                $parentIds = Mage::getResourceSingleton('bundle/selection')->getParentIdsByChild($observer->getEvent()->getProduct()->getId());
                if(count($parentIds) > 0 && !empty($parentIds)) {
                    Mage::getModel("klevu_search/product_sync")->updateSpecificProductIds($parentIds);
                }
            }
        } catch(Exception $e) {
            Mage::helper('klevu_search')->log(Zend_Log::DEBUG, sprintf("error while updating bundle product id :\n%s", $e->getMessage()));
        }

        $image = $observer->getEvent()->getProduct()->getImage();
        if(($image != "no_selection") && (!empty($image))) {
            try {
                $imageResized = Mage::getBaseDir('media').DS."klevu_images".$image;
                $baseImageUrl = Mage::getBaseDir('media').DS."catalog".DS."product".$image;
                if(file_exists($baseImageUrl)) {
                    list($width, $height, $type, $attr)= getimagesize($baseImageUrl); 
                    if($width > 200 && $height > 200) {
                            if(file_exists($imageResized)) {
                                if (!unlink('media/klevu_images'. $image))
                                {
                                    Mage::helper('klevu_search')->log(Zend_Log::DEBUG, sprintf("Image Deleting Error:\n%s", $image));  
                                }
                            }

                            Mage::getModel("klevu_search/product_sync")->thumbImageObj($baseImageUrl, $imageResized);
                    }
                }
            } catch(Exception $e) {
                 Mage::helper('klevu_search')->log(Zend_Log::DEBUG, sprintf("Image Error:\n%s", $e->getMessage()));
            }
        }
    }
  
    /**
     * Apply model rewrites for the search landing page, if it is enabled.
     *
     * @param Varien_Event_Observer $observer
     */
    public function applyLandingPageModelRewrites(Varien_Event_Observer $observer) 
    {

        if (Mage::helper("klevu_search/config")->isLandingEnabled() == 1 && Mage::helper("klevu_search/config")->isExtensionConfigured()) {
            $rewrites = array(
                "global/models/catalogsearch_resource/rewrite/fulltext_collection"         => "Klevu_Search_Model_CatalogSearch_Resource_Fulltext_Collection",
                "global/models/catalogsearch_mysql4/rewrite/fulltext_collection"           => "Klevu_Search_Model_CatalogSearch_Resource_Fulltext_Collection",
                "global/models/catalogsearch/rewrite/layer_filter_attribute"               => "Klevu_Search_Model_CatalogSearch_Layer_Filter_Attribute",
                "global/models/catalog/rewrite/config"                                     => "Klevu_Search_Model_Catalog_Model_Config",
                "global/models/catalog/rewrite/layer_filter_price"                         => "Klevu_Search_Model_CatalogSearch_Layer_Filter_Price",
                "global/models/catalog/rewrite/layer_filter_category"                      => "Klevu_Search_Model_CatalogSearch_Layer_Filter_Category",
                "global/models/catalog_resource/rewrite/layer_filter_attribute"            => "Klevu_Search_Model_CatalogSearch_Resource_Layer_Filter_Attribute",
                "global/models/catalog_resource_eav_mysql4/rewrite/layer_filter_attribute" => "Klevu_Search_Model_CatalogSearch_Resource_Layer_Filter_Attribute"
            );

            $config = Mage::app()->getConfig();
            foreach ($rewrites as $key => $value) {
                $config->setNode($key, $value);
            }
        }
    }

    /**
     * Call remove testmode
     */
    public function removeTest() 
    {
        Mage::getModel("klevu_search/product_sync")->removeTestMode();    
        
    }
    
    /**
     * make prodcuts for update when category change products
     */
    public function setCategoryProductsToSync(Varien_Event_Observer $observer) 
    {
        try {
            $updatedProductsIds = $observer->getData('product_ids');
            
            if (count($updatedProductsIds) == 0) {
                return;
            }

            Mage::getModel("klevu_search/product_sync")->updateSpecificProductIds($updatedProductsIds);
        } catch (Exception $e) {
            Mage::helper('klevu_search')->log(Zend_Log::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
        }
        
    }
    
    /**
     * Update the product ratings value in product attribute
     */
    public function ratingsUpdate(Varien_Event_Observer $observer)
    {
        try {
            $object = $observer->getEvent()->getObject();
            $statusId = $object->getStatusId();
            $allStores = Mage::app()->getStores();
            if($statusId == 1) {
                $productId = $object->getEntityPkValue();
                $ratingObj = Mage::getModel('rating/rating')->getEntitySummary($productId);
                $ratings = $ratingObj->getSum()/$ratingObj->getCount();
                $entity_type = Mage::getSingleton("eav/entity_type")->loadByCode("catalog_product");
                $entity_typeid = $entity_type->getId();
                $attributecollection = Mage::getModel("eav/entity_attribute")->getCollection()->addFieldToFilter("entity_type_id", $entity_typeid)->addFieldToFilter("attribute_code", "rating");
                if(count($attributecollection) > 0) {
                    if(count($object->getData('stores')) > 0) {
                        foreach($object->getData('stores') as $key => $value) {
                            Mage::getModel('catalog/product_action')->updateAttributes(array($productId), array('rating'=>$ratings), $value);
                        }
                    }

                    /* update attribute */
                    if(count($allStores) > 1) {
                        Mage::getModel('catalog/product_action')->updateAttributes(array($productId), array('rating'=>0), 0);
                    }

                    /* mark product for update to sync data with klevu */
                    Mage::getModel('klevu_search/product_sync')->updateSpecificProductIds(array($productId));
                }
            }
        } catch (Exception $e) {
            Mage::helper('klevu_search')->log(Zend_Log::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
        }
    }
    
    /**
     * Update the product ids to sync with klevu for rule 
     */
    public function catalogRulePriceChange(Varien_Event_Observer $observer)
    {
        try {
            $obj = $observer->getEvent()->getRule();
            $matchIds = $obj->getMatchingProductIds();
            $rows = array();
            if(!empty($matchIds)) {
                if (version_compare(Mage::getVersion(), '1.7.0.2', '<=')===true) {
                    $rows = $matchIds;
                } else {
                    foreach($matchIds as $key => $value) {
                        if(is_array($value)) {
                            if (in_array(1, $value)) {
                                $rows[] = $key;
                            }
                        }
                    }
                }
            }

            if(!empty($rows)){
                Mage::getModel("klevu_search/product_sync")->updateSpecificProductIds($rows);
            }
        } catch (Exception $e) {
            Mage::helper('klevu_search')->log(Zend_Log::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
        }
    }
    
    /**
     * Update the product id when stock item changed through out api
     */
    public function updateStock($observer)
    {
        try {
            if (version_compare(Mage::getVersion(), '1.7.0.2', '<=') !== true) {
                $productId = $observer->getEvent()->getItem()->getProductId();
                if(!empty($productId)) {
                    Mage::getModel("klevu_search/product_sync")->updateSpecificProductIds(array($productId));
                }
            }
        } catch(Exception $e) {
            Mage::helper('klevu_search')->log(Zend_Log::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
        }
    }
    
    
    
}
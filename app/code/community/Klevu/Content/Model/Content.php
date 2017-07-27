<?php
class Klevu_Content_Model_Content extends Klevu_Search_Model_Product_Sync
{
    public function _construct() {
        parent::_construct();

        $this->addData(array(
            "connection" => Mage::getModel("core/resource")->getConnection("core_write")
        ));
    }

    public function getJobCode() {
        return "klevu_search_content_sync";
    }

    /**
     * Perform Content Sync on any configured stores, adding new content, updating modified and
     * deleting removed content since last sync.
     */
    public function run()
    {
  
        // Sync Data only for selected store from config wizard
        $session = Mage::getSingleton('klevu_search/session');
        $firstSync = $session->getFirstSync();
        if(!empty($firstSync)){
            $onestore = Mage::app()->getStore($firstSync);
            $this->reset();
            if (!Mage::helper("content")->isCmsSyncEnabled($onestore->getId())) {
                return;
            }
            if (!$this->setupSession($onestore)) {
                return;
            }
            $this->syncCmsData($onestore);
            return;
        }
        
        if ($this->isRunning(2)) {
            // Stop if another copy is already running
            $this->log(Zend_Log::INFO, "Stopping because another copy is already running.");
            return;
        }
        
        // Sync all store cms Data 
        $stores = Mage::app()->getStores();
        foreach($stores as $store) {
            /** @var Mage_Core_Model_Store $store */
            $this->reset();
            if (!Mage::helper("content")->isCmsSyncEnabled($store->getId())) {
                continue;
            }
            if (!$this->setupSession($store)) {
                continue;
            }
            $this->syncCmsData($store);
        }

    }
    
    public function syncCmsData($store){
    
            if ($this->rescheduleIfOutOfMemory()) {
                return;
            }

            $cPgaes = Mage::helper("content")->getExcludedPages($store);
            if(count($cPgaes) > 0) {
                foreach($cPgaes as $key => $cvalue){
                    $pageids[]  = intval($cvalue['cmspages']);
                }
            } else {
                $pageids = "";
            }
            
            if(!empty($pageids)){
                $eids = implode("','",$pageids);
            } else {
                 $eids = $pageids;
            }

            $this->log(Zend_Log::INFO, sprintf("Starting Cms sync for %s (%s).", $store->getWebsite()->getName() , $store->getName()));
            $actions = array(
                    'delete' => $this->getConnection()
                        ->select()
                        /*
                         * Select synced cms in the current store/mode that 
                         * are no longer enabled
                         */
                        ->from(
                                    array('k' => $this->getTableName("klevu_search/product_sync")),
                                    array('page_id' => "k.product_id")
                                   
                        )
                        ->joinLeft(
                            array('c' => $this->getTableName("cms_page")),
                            "k.product_id = c.page_id",
                            ""
                        )
                        ->joinLeft(
                            array('v' => $this->getTableName("cms_page_store")),
                            "v.page_id = c.page_id",
                            ""
                        )
                        ->where("((k.store_id = :store_id AND v.store_id != 0) AND (k.type = :type) AND (k.product_id NOT IN ?)) OR ( (k.product_id IN ('".$eids."') OR (c.page_id IS NULL) OR (c.is_active = 0)) AND (k.type = :type) AND k.store_id = :store_id)",
                            $this->getConnection()
                                ->select()
                                ->from(
                                    array('i' => $this->getTableName("cms_page_store")),
                                    array('page_id' => "i.page_id")
                                )
                                ->where('i.page_id NOT IN (?)', $pageids)
                               // ->where("i.store_id = :store_id")
                        )
                        ->group(array('k.product_id'))
                        ->bind(array(
                            'store_id'=> $store->getId(),
                            'type' => "pages",
                        )),

                    'update' => 
                            $this->getConnection()
                                ->select()
                                /*
                                 * Select pages for the current store/mode
                                 * have been updated since last sync.
                                 */
                                 ->from(
                                    array('k' => $this->getTableName("klevu_search/product_sync")),
                                    array('page_id' => "k.product_id")
                                   
                                )
                                ->join(
                                    array('c' => $this->getTableName("cms_page")),
                                    "c.page_id = k.product_id",
                                    ""
                                )
                                ->joinLeft(
                                    array('v' => $this->getTableName("cms_page_store")),
                                    "v.page_id = c.page_id AND v.store_id = :store_id",
                                    ""
                                )
                                ->where("(c.is_active = 1) AND (k.type = :type) AND (k.store_id = :store_id) AND (c.update_time > k.last_synced_at)")
                                ->where('c.page_id NOT IN (?)', $pageids)
                        ->bind(array(
                            'store_id' => $store->getId(),
                            'type'=> "pages",
                        )),

                    'add' =>  $this->getConnection()
                                ->select()
                                ->union(array(
                                $this->getConnection()
                                ->select()
                                /*
                                 * Select pages for the current store/mode
                                 * have been updated since last sync.
                                 */
                                ->from(
                                    array('p' => $this->getTableName("cms_page")),
                                    array('page_id' => "p.page_id")
                                )
                                ->where('p.page_id NOT IN (?)', $pageids)
                                ->joinLeft(
                                    array('v' => $this->getTableName("cms_page_store")),
                                    "p.page_id = v.page_id",
                                    ""
                                )
                                ->joinLeft(
                                    array('k' => $this->getTableName("klevu_search/product_sync")),
                                    "p.page_id = k.product_id AND k.store_id = :store_id AND k.test_mode = :test_mode AND k.type = :type",
                                    ""
                                )
                                ->where("p.is_active = 1 AND k.product_id IS NULL AND v.store_id =0"),
                                $this->getConnection()
                                ->select()
                                /*
                                 * Select pages for the current store/mode
                                 * have been updated since last sync.
                                 */
                                ->from(
                                    array('p' => $this->getTableName("cms_page")),
                                    array('page_id' => "p.page_id")
                                )
                                ->where('p.page_id NOT IN (?)', $pageids)
                                ->join(
                                    array('v' => $this->getTableName("cms_page_store")),
                                    "p.page_id = v.page_id AND v.store_id = :store_id",
                                    ""
                                )
                                ->joinLeft(
                                    array('k' => $this->getTableName("klevu_search/product_sync")),
                                    "v.page_id = k.product_id AND k.store_id = :store_id AND k.test_mode = :test_mode AND k.type = :type",
                                    ""
                                )
                                ->where("p.is_active = 1 AND k.product_id IS NULL")
                            ))    
                        ->bind(array(
                            'type' => "pages",
                            'store_id' => $store->getId(),
                            'test_mode' => $this->isTestModeEnabled(),
                        )),
                );
            $errors = 0;
            foreach($actions as $action => $statement) {
                if ($this->rescheduleIfOutOfMemory()) {
                    return;
                }
                $method = $action . "cms";
                $cms_pages = $this->getConnection()->fetchAll($statement, $statement->getBind());
                $total = count($cms_pages);
                $this->log(Zend_Log::INFO, sprintf("Found %d Cms Pages to %s.", $total, $action));
                $pages = ceil($total / static ::RECORDS_PER_PAGE);
                for ($page = 1; $page <= $pages; $page++) {
                    if ($this->rescheduleIfOutOfMemory()) {
                        return;
                    }
                    $offset = ($page - 1) * static ::RECORDS_PER_PAGE;
                    $result = $this->$method(array_slice($cms_pages, $offset, static ::RECORDS_PER_PAGE));
                    if ($result !== true) {
                        $errors++;
                        $this->log(Zend_Log::ERR, sprintf("Errors occurred while attempting to %s cms pages %d - %d: %s", $action, $offset + 1, ($offset + static ::RECORDS_PER_PAGE <= $total) ? $offset + static ::RECORDS_PER_PAGE : $total, $result));
                    }
                }
            }
            $this->log(Zend_Log::INFO, sprintf("Finished cms page sync for %s (%s).", $store->getWebsite()->getName() , $store->getName()));
    }
    /**
     * Delete the given pages from Klevu Search. Returns true if the operation was
     * successful, or the error message if the operation failed.
     *
     * @param array $data List of pages to delete. Each element should be an array
     *                    containing an element with "page_id" as the key and page id as
     *                    the value.
     *
     * @return bool|string
     */
    protected function deletecms(array $data)
    {
		Mage::getSingleton('core/session')->setKlevuFailedFlag(0); 
		$baseDomain = $this->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK,true);
		foreach($data as $key => $value){
			$data[$key]['url'] = $baseDomain; 
		}
        $total = count($data);
        $response = Mage::getModel('klevu_search/api_action_deleterecords')->setStore($this->getStore())->execute(array(
            'sessionId' => $this->getSessionId() ,
            'records' => array_map(function ($v)
            {
                return array(
                    'id' => "pageid_" . $v['page_id'],
					'url' => $v['url']
                );
            }
            , $data)
        ));
        if ($response->isSuccessful()) {
			
            $connection = $this->getConnection();
            $select = $connection->select()->from(array(
                'k' => $this->getTableName("klevu_search/product_sync")
            ))->where("k.store_id = ?", $this->getStore()->getId())->where("k.type = ?", "pages")->where("k.test_mode = ?", $this->isTestModeEnabled());
            $skipped_record_ids = array();
            if ($skipped_records = $response->getSkippedRecords()) {
                $skipped_record_ids = array_flip($skipped_records["index"]);
            }
            $or_where = array();
            for ($i = 0; $i < count($data); $i++) {
                if (isset($skipped_record_ids[$i])) {
                    continue;
                }
                $or_where[] = sprintf("(%s)", $connection->quoteInto("k.product_id = ?", $data[$i]['page_id']));
            }
            $select->where(implode(" OR ", $or_where));
            $connection->query($select->deleteFromSelect("k"));
            $skipped_count = count($skipped_record_ids);
            if ($skipped_count > 0) {
                return sprintf("%d cms%s failed (%s)", $skipped_count, ($skipped_count > 1) ? "s" : "", implode(", ", $skipped_records["messages"]));
            }
            else {
                return true;
            }
        } else {
			Mage::getSingleton('core/session')->setKlevuFailedFlag(1);
            return sprintf("%d cms%s failed (%s)", $total, ($total > 1) ? "s" : "", $response->getMessage());
        }
    }
    /**
     * Add the given pages to Klevu Search. Returns true if the operation was successful,
     * or the error message if it failed.
     *
     * @param array $data List of pages to add. Each element should be an array
     *                    containing an element with "page_id" as the key and page id as
     *                    the value.
     *
     * @return bool|string
     */
    protected function addCms(array $data)
    {
		Mage::getSingleton('core/session')->setKlevuFailedFlag(0);
        $total = count($data);
        $data = $this->addCmsData($data);
        $response = Mage::getModel('klevu_search/api_action_addrecords')->setStore($this->getStore())->execute(array(
            'sessionId' => $this->getSessionId() ,
            'records' => $data
        ));
        if ($response->isSuccessful()) {
            $skipped_record_ids = array();
            if ($skipped_records = $response->getSkippedRecords()) {
                $skipped_record_ids = array_flip($skipped_records["index"]);
            }
            $sync_time = Mage::helper("klevu_search/compat")->now();
            foreach($data as $i => & $record) {
                if (isset($skipped_record_ids[$i])) {
                    unset($data[$i]);
                    continue;
                }
                $ids[$i] = explode("_", $data[$i]['id']);
                $record = array(
                    $ids[$i][1],
                    0,
                    $this->getStore()->getId() ,
                    $this->isTestModeEnabled() ,
                    $sync_time,
                    "pages"
                );
            }
            $this->getConnection()->insertArray($this->getTableName('klevu_search/product_sync') , array(
                "product_id",
                "parent_id",
                "store_id",
                "test_mode",
                "last_synced_at",
                "type"
            ) , $data);
            $skipped_count = count($skipped_record_ids);
            if ($skipped_count > 0) {
                return sprintf("%d cms%s failed (%s)", $skipped_count, ($skipped_count > 1) ? "s" : "", implode(", ", $skipped_records["messages"]));
            }
            else {
                return true;
            }
        }
        else {
			Mage::getSingleton('core/session')->setKlevuFailedFlag(1);
            return sprintf("%d cms%s failed (%s)", $total, ($total > 1) ? "s" : "", $response->getMessage());
        }
    }
    /**
     * Add the page Sync data to each page in the given list. Updates the given
     * list directly to save memory.
     *
     * @param array $pages An array of pages. Each element should be an array with
     *                        containing an element with "id" as the key and the Page
     *                        ID as the value.
     *
     * @return $this
     */
    protected function addcmsData(&$pages)
    {
        $page_ids = array();
        foreach($pages as $key => $value) {
            $page_ids[] = $value["page_id"];
        }
        if ($this->getStore()->isFrontUrlSecure()) {
            $base_url = $this->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true);
        }
        else {
            $base_url = $this->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        }
        $data = Mage::getModel('cms/page')->getCollection()->addFieldToSelect("*")->addFieldToFilter('page_id', array(
            'in' => $page_ids
        ));
        $cms_data = $data->load()->getData();
        foreach($cms_data as $key => $value) {
            $value["name"] = $value["title"];
            $value["id"] = "pageid_" . $value["page_id"];
            $value["url"] = $base_url . $value["identifier"];
            $value["desc"] = preg_replace('#\{{.*?\}}#s','',strip_tags(Mage::helper("content")->ripTags($value["content"])));
            $value["metaDesc"] = $value["meta_description"] . $value["meta_keywords"];
            $value["shortDesc"] = substr(preg_replace('#\{{.*?\}}#s','',strip_tags(Mage::helper("content")->ripTags($value["content"]))),0,200);
            $value["listCategory"] = "KLEVU_CMS";
            $value["category"] = "pages";
            $value["salePrice"] = 0;
            $value["currency"] = "USD";
            $value["inStock"] = "yes";
            $cms_data_new[] = $value;
        }
        return $cms_data_new;
    }
    /**
     * Update the given pages on Klevu Search. Returns true if the operation was successful,
     * or the error message if it failed.
     *
     * @param array $data List of Pages to update. Each element should be an array
     *                    containing an element with "page_id" as the key and page id as
     *                    the value
     *
     * @return bool|string
     */
    protected function updateCms(array $data)
    {
		Mage::getSingleton('core/session')->setKlevuFailedFlag(0);
        $total = count($data);
        $data = $this->addCmsData($data);
        $response = Mage::getModel('klevu_search/api_action_updaterecords')->setStore($this->getStore())->execute(array(
            'sessionId' => $this->getSessionId() ,
            'records' => $data
        ));
        if ($response->isSuccessful()) {
            $helper = Mage::helper('klevu_search');
            $connection = $this->getConnection();
            $skipped_record_ids = array();
            if ($skipped_records = $response->getSkippedRecords()) {
                $skipped_record_ids = array_flip($skipped_records["index"]);
            }
            $where = array();
            for ($i = 0; $i < count($data); $i++) {
                if (isset($skipped_record_ids[$i])) {
                    continue;
                }
                $ids[$i] = explode("_", $data[$i]['id']);
                $where[] = sprintf("(%s AND %s AND %s)", $connection->quoteInto("product_id = ?", $ids[$i][1]) , $connection->quoteInto("parent_id = ?", 0) , $connection->quoteInto("type = ?", "pages"));
            }
            $where = sprintf("(%s) AND (%s) AND (%s)", $connection->quoteInto("store_id = ?", $this->getStore()->getId()) , $connection->quoteInto("test_mode = ?", $this->isTestModeEnabled()) , implode(" OR ", $where));
            $this->getConnection()->update($this->getTableName('klevu_search/product_sync') , array(
                'last_synced_at' => Mage::helper("klevu_search/compat")->now()
            ) , $where);
            $skipped_count = count($skipped_record_ids);
            if ($skipped_count > 0) {
                return sprintf("%d cms%s failed (%s)", $skipped_count, ($skipped_count > 1) ? "s" : "", implode(", ", $skipped_records["messages"]));
            }
            else {
                return true;
            }
        }
        else {
			Mage::getSingleton('core/session')->setKlevuFailedFlag(1);
            return sprintf("%d cms%s failed (%s)", $total, ($total > 1) ? "s" : "", $response->getMessage());
        }
    }

}
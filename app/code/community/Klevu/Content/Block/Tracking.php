<?php
class Klevu_Content_Block_Tracking extends Mage_Core_Block_Template
{
    /**
     * JSON of required tracking parameter for Klevu Product Click Tracking, based on current product
     * @return string
     * @throws Exception
     */
    public function getJsonTrackingData() {
    
        $api_key = Mage::helper('klevu_search/config')->getJsApiKey();
        // Get current Cms page object
        $page = Mage::getSingleton('cms/page');
        if ($page->getId()) {
            $content = array(
                'klevu_apiKey' => $api_key,
                'klevu_term'   => '',
                'klevu_type'   => 'clicked',
                'klevu_productId' => $page->getPageId(),
                'klevu_productName' => $page->getTitle(),
                'klevu_productUrl' => $page->getIdentifier(),
                'Klevu_typeOfRecord' => 'KLEVU_CMS'
            );
            return json_encode($content);
        }
    }
}
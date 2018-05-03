<?php

class Klevu_Search_Block_Adminhtml_Wizard_Configure_Store extends Mage_Adminhtml_Block_Template
{

    /**
     * Return the submit URL for the store configuration form.
     *
     * @return string
     */
    protected function getFormActionUrl() 
    {
        return $this->getUrl("adminhtml/klevu_search_wizard/configure_store_post");
    }

    /**
     * Return the list of stores that can be selected to be configured (i.e. haven't
     * been configured already), organised by website name and group name.
     *
     * @return array
     */
    protected function getStoreSelectData() 
    {
        $stores = Mage::app()->getStores(false);
        $config = Mage::helper("klevu_search/config");

        $data = array();

        foreach ($stores as $store) {
            /** @var Mage_Core_Model_Store $store */
            if ($config->getJsApiKey($store) && $config->getRestApiKey($store)) {
                // Skip already configured stores
                continue;
            }

            $website = $store->getWebsite()->getName();
            $group = $store->getGroup()->getName();

            if (!isset($data[$website])) {
                $data[$website] = array();
            }

            if (!isset($data[$website][$group])) {
                $data[$website][$group] = array();
            }

            $data[$website][$group][] = $store;
        }

        return $data;
    }
}

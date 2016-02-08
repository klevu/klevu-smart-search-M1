<?php

class Klevu_Search_Block_Adminhtml_Wizard_Configure_User extends Mage_Adminhtml_Block_Template {

    /**
     * Return the submit URL for the user configuration form.
     *
     * @return string
     */
    protected function getFormActionUrl() {
        return $this->getUrl('adminhtml/klevu_search_wizard/configure_user_post');
    }

    /**
     * Return the base URL for the store.
     *
     * @return string
     */
    protected function getStoreUrl() {
        return $this->getBaseUrl();
    }
    

    
}

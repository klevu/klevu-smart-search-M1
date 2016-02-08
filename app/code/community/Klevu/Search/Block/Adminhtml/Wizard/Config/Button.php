<?php
/**
 * Class Klevu_Search_Block_Adminhtml_Wizard_Config_Button
 *
 * @method string getHtmlId()
 * @method string getWizardUrl()
 */
class Klevu_Search_Block_Adminhtml_Wizard_Config_Button extends Mage_Adminhtml_Block_System_Config_Form_Field {

    protected function _prepareLayout() {
        parent::_prepareLayout();

        // Set the default template
        if (!$this->getTemplate()) {
            $this->setTemplate('klevu/search/wizard/config/button.phtml');
        }

        return $this;
    }

    public function render(Varien_Data_Form_Element_Abstract $element) {
        // Only show the current scope hasn't been configured yet
        switch($element->getScope()) {
            case "stores":
                if ($this->hasApiKeys($element->getScopeId())) {
                    return "";
                }
                break;
            case "websites":
                $website = Mage::app()->getWebsite($element->getScopeId());
                if ($this->hasApiKeys($website->getStores())) {
                    return "";
                }
                break;
            default:
                if ($this->hasApiKeys()) {
                    return "";
                }
        }

        // Remove the scope information so it doesn't get printed out
        $element
            ->unsScope()
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();

        return parent::render($element);
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        $this->addData(array(
            'html_id' => $element->getHtmlId(),
            'wizard_url' => $this->getUrl("adminhtml/klevu_search_wizard/configure_user")
        ));

        return $this->_toHtml();
    }

    /**
     * Check if the given stores all have Klevu API keys. If no stores are given, checks
     * all configured stores.
     *
     * @param null $stores
     *
     * @return bool true if all stores have API keys, false otherwise.
     */
    protected function hasApiKeys($stores = null) {
        $config = Mage::helper("klevu_search/config");

        if ($stores === null) {
            $stores = Mage::app()->getStores(false);
        }

        if (!is_array($stores)) {
            $stores = array($stores);
        }

        foreach ($stores as $store) {
            if (!$config->getJsApiKey($store) || !$config->getRestApiKey($store)) {
                return false;
            }
        }

        return true;
    }
}

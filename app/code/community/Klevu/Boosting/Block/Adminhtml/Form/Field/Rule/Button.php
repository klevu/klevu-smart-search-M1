<?php

/**
 * Class Klevu_Search_Block_Adminhtml_Form_Field_Sync_Button
 *
 * @method setStoreId($id)
 * @method string getStoreId()
 */
class Klevu_Boosting_Block_Adminhtml_Form_Field_Rule_Button extends Mage_Adminhtml_Block_System_Config_Form_Field {

    protected function _prepareLayout() {
        parent::_prepareLayout();

        // Set the default template
        if (!$this->getTemplate()) {
            $this->setTemplate('klevu/boosting/form/field/rule/button.phtml');
        }

        return $this;
    }

    public function render(Varien_Data_Form_Element_Abstract $element) {
        if ($element->getScope() == "stores") {
            $this->setStoreId($element->getScopeId());
        }

        // Remove the scope information so it doesn't get printed out
        $element
            ->unsScope()
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();

        return parent::render($element);
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        $url_params = ($this->getStoreId()) ? array("store" => $this->getStoreId()) : array();
        $label_suffix = "";

        $this->addData(array(
            "html_id"         => $element->getHtmlId(),
            "button_label"    => sprintf("Configure Product Boosting Rules%s", $label_suffix),
            "destination_url" => $this->getUrl("admin_boosting/adminhtml_boost", $url_params),
        ));

        return $this->_toHtml();
    }
}

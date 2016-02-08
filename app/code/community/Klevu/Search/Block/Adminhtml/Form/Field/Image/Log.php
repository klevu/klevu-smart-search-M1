<?php

/**
 * Class Klevu_Search_Block_Adminhtml_Form_Field_Image_Button
 *
 * @method setStoreId($id)
 * @method string getStoreId()
 */
 
class Klevu_Search_Block_Adminhtml_Form_Field_Image_Log extends Mage_Adminhtml_Block_System_Config_Form_Field {

    protected function _prepareLayout() {
        parent::_prepareLayout();

        // Set the default template
        if (!$this->getTemplate()) {
            $this->setTemplate('klevu/search/form/field/sync/logbutton.phtml');
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
        $url_params = array("debug" => "klevu");
        $label_suffix = ($this->getStoreId()) ? " for This Store" : "";

        $this->addData(array(
            "html_id"         => $element->getHtmlId(),
            "button_label"    => sprintf("Send Log"),
            "destination_url" => $this->getUrl("search/index/runexternaly", $url_params)
        ));

        return $this->_toHtml();
    }
}

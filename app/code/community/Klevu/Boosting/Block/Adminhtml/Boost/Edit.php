<?php
class Klevu_Boosting_Block_Adminhtml_Boost_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_objectId = "id";
        $this->_blockGroup = "boosting";
        $this->_controller = "adminhtml_boost";
        $this->_updateButton("save", "label", Mage::helper("boosting")->__("Save Rule"));
        $this->_updateButton("delete", "label", Mage::helper("boosting")->__("Delete Rule"));
        $this->_addButton(
            "saveandcontinue", array(
            "label" => Mage::helper("boosting")->__("Save And Continue Edit") ,
            "onclick" => "saveAndContinueEdit()",
            "class" => "save",
            ), -100
        );
        $this->_formScripts[] = "function saveAndContinueEdit(){editForm.submit($('edit_form').action+'back/edit/');}";

    }
    public function getHeaderText()
    {
        if (Mage::registry("boost_data") && Mage::registry("boost_data")->getId()) {
            return Mage::helper("boosting")->__("Edit Rule '%s'", $this->htmlEscape(Mage::registry("boost_data")->getName()));
        }
        else {
            return Mage::helper("boosting")->__("Add Rule");
        }
    }
}
<?php
class Klevu_Boosting_Block_Adminhtml_Boost_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId("boost_tabs");
        $this->setDestElementId("edit_form");
        $this->setTitle(Mage::helper("boosting")->__("Klevu Rule Information"));
    }
    protected function _beforeToHtml()
    {
        $this->addTab(
            "form_general_section", array(
            "label" => Mage::helper("boosting")->__("Rule Information") ,
            "title" => Mage::helper("boosting")->__("Rule Information") ,
            "content" => $this->getLayout()->createBlock("boosting/adminhtml_boost_edit_tab_form")->toHtml() ,
            )
        );
        $this->addTab(
            'form_condition_section', array(
            'label' => Mage::helper('boosting')->__('Conditions') ,
            'title' => Mage::helper('boosting')->__('Conditions') ,
            'content' => $this->getLayout()->createBlock('boosting/adminhtml_boost_edit_tab_conditions')->toHtml() ,
            )
        );
        $this->addTab(
            'form_action_section', array(
            'label' => Mage::helper('boosting')->__('Actions') ,
            'title' => Mage::helper('boosting')->__('Actions') ,
            'content' => $this->getLayout()->createBlock('boosting/adminhtml_boost_edit_tab_actions')->toHtml() ,
            )
        );
        return parent::_beforeToHtml();
    }
}

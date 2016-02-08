<?php
class Klevu_Boosting_Block_Adminhtml_Boost_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset("boosting_form", array(
            "legend" => Mage::helper("boosting")->__("Rule Information")
        ));
        $fieldset->addField("name", "text", array(
            "label" => Mage::helper("boosting")->__("Rule Name") ,
            "class" => "required-entry",
            "required" => true,
            "name" => "name",
        ));
        $fieldset->addField("description", "textarea", array(
            "label" => Mage::helper("boosting")->__("Description") ,
            "class" => "required-entry",
            "required" => true,
            "name" => "description",
        ));
        $fieldset->addField('status', 'select', array(
            'label' => $this->__('Status') ,
            'title' => $this->__('Status') ,
            'name' => 'status',
            'required' => true,
            'options' => array(
                '1' => $this->__('Active') ,
                '0' => $this->__('Inactive') ,
            ) ,
        ));
        if (Mage::getSingleton("adminhtml/session")->getBoostData()) {
            $form->setValues(Mage::getSingleton("adminhtml/session")->getBoostData());
            Mage::getSingleton("adminhtml/session")->setBoostData(null);
        }
        else if (Mage::registry("boost_data")) {
            $form->setValues(Mage::registry("boost_data")->getData());
        }
        return parent::_prepareForm();
    }
}

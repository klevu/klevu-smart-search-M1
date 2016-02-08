<?php
class Klevu_Boosting_Block_Adminhtml_Boost_Edit_Tab_Actions extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset("boosting_form", array(
            "legend" => Mage::helper("boosting")->__("Specify the boosting score to be assigned to the
   products matching the conditions of your rule")
        ));
        $afterElementHtml = '<p class="nm"><small>Please enter a value between 1 and 999. If a product satisfies all the 
condition, the boosting score given here is assigned to the product.</small></p>';
        $fieldset->addField("boosting", "text", array(
            "label" => Mage::helper("boosting")->__("Boost Value") ,
            "class" => "required-entry validate-number validate-number-range number-range-1-999",
            "required" => true,
            "name" => "boosting",
            'after_element_html' => $afterElementHtml,
        ));
        if (Mage::getSingleton("adminhtml/session")->getBoostData()) {
            $form->setValues(Mage::getSingleton("adminhtml/session")->getBoostData());
            Mage::getSingleton("adminhtml/session")->setBoostData(null);
        }
        elseif (Mage::registry("boost_data")) {
            $form->setValues(Mage::registry("boost_data")->getData());
        }
        return parent::_prepareForm();
    }
}

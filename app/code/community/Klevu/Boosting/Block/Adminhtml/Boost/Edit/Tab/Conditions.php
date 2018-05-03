<?php
class Klevu_Boosting_Block_Adminhtml_Boost_Edit_Tab_Conditions extends Mage_Adminhtml_Block_Widget_Form implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function getTabLabel()
    {
        return $this->__('Conditions');
    }
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }
    public function canShowTab()
    {
        return true;
    }
    public function isHidden()
    {
        return false;
    }
    protected function _prepareForm()
    {
        $boostModel = Mage::registry('boost_data');
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('rule_');
        $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')->setTemplate('promo/fieldset.phtml')->setNewChildUrl($this->getUrl('*/boost/newConditionHtml/form/rule_conditions_fieldset'));
        $fieldset = $form->addFieldset(
            'conditions_fieldset', array(
            'legend' => $this->__(
                'Specify "Conditions" when the rule should be executed (Note: Only the attributes which have been enabled for promotion use are visible in the dropdown below (see Catalog > Attributes > Manage Attributes > Choose your attribute > Properties > Use for Promo Rule Conditions).
)'
            )
            )
        )->setRenderer($renderer);
        $fieldset->addField(
            'conditions', 'text', array(
            'type' => 'conditions',
            'label' => $this->__('Conditions') ,
            'title' => $this->__('Conditions') ,
            'required' => true,
            )
        )->setRule($boostModel)->setRenderer(Mage::getBlockSingleton('rule/conditions'));
        $form->setValues($boostModel->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }
}

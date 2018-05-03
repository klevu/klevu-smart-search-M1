<?php
class Klevu_Content_Block_Adminhtml_Form_System_Config_Field_Select extends Mage_Adminhtml_Block_Html_Select
{
    protected function _toHtml()
    {
        $this->setName($this->getInputName());
        $this->setClass('select');
        return trim(preg_replace('/\s+/', ' ', parent::_toHtml()));
    }
}

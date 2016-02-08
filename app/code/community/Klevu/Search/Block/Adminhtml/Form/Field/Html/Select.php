<?php

class Klevu_Search_Block_Adminhtml_Form_Field_Html_Select extends Mage_Adminhtml_Block_Html_Select {

    public function setInputName($value) {
        return $this->setName($value);
    }
}

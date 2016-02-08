<?php

class Klevu_Search_Block_Adminhtml_System_Config_Form_Field extends Mage_Adminhtml_Block_System_Config_Form_Field {
    
    /**
     * Enter description here...
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element) {
        if (version_compare(Mage::getVersion(), '1.7.0.1', '<')) {
            if (Mage::app()->getRequest()->getParam('section') == "klevu_search") {
                $id       = $element->getHtmlId();
                $features = Mage::helper('klevu_search/config')->getFeaturesUpdate($element->getHtmlId());
                if (!empty($features)) {
                    $style        = 'class="klevu-disabled"';
                    $upgrade_text = '';
                    if (!empty($features['upgrade_message']) || !empty($features['upgrade_label'])) {
                        $upgrade_text .= "<div class='klevu-upgrade-block'>";
                        if (!empty($features['upgrade_message'])) {
                            $upgrade_text .= $features['upgrade_message'];
                        }
                        if (!empty($features['upgrade_label'])) {
                            $upgrade_text .= "<br/><button type='button' onClick=upgradeLink('" . $features["upgrade_url"] . "')>" . $features['upgrade_label'] . "</button>";
                        }
                        $upgrade_text .= "</div>";
                    }
                } else {
                    $style        = '';
                    $upgrade_text = '';
                }
                $useContainerId     = $element->getData('use_container_id');
                $html               = '<tr id="row_' . $id . '">' . '<td class="label"><label for="' . $id . '" ' . $style . '>' . $element->getLabel() . '</label>' . $upgrade_text . '</td>';
                // $isDefault = !$this->getRequest()->getParam('website') && !$this->getRequest()->getParam('store');
                $isMultiple         = $element->getExtType() === 'multiple';
                // replace [value] with [inherit]
                $namePrefix         = preg_replace('#\[value\](\[\])?$#', '', $element->getName());
                $options            = $element->getValues();
                $addInheritCheckbox = false;
                if ($element->getCanUseWebsiteValue()) {
                    $addInheritCheckbox = true;
                    $checkboxLabel      = Mage::helper('adminhtml')->__('Use Website');
                } elseif ($element->getCanUseDefaultValue()) {
                    $addInheritCheckbox = true;
                    $checkboxLabel      = Mage::helper('adminhtml')->__('Use Default');
                }
                if ($addInheritCheckbox) {
                    $inherit = $element->getInherit() == 1 ? 'checked="checked"' : '';
                    if ($inherit) {
                        $element->setDisabled(true);
                    }
                }
                // Code added by klevu
                if (!empty($features)) {
                    $element->setDisabled(true);
                    $element->setValue(0);
                }
                $html .= '<td class="value">';
                $html .= $this->_getElementHtml($element);
                if ($element->getComment()) {
                    $html .= '<p class="note"><span>' . $element->getComment() . '</span></p>';
                }
                $html .= '</td>';
                if ($addInheritCheckbox) {
                    $defText = $element->getDefaultValue();
                    if ($options) {
                        $defTextArr = array();
                        foreach ($options as $k => $v) {
                            if ($isMultiple) {
                                if (is_array($v['value']) && in_array($k, $v['value'])) {
                                    $defTextArr[] = $v['label'];
                                }
                            } elseif ($v['value'] == $defText) {
                                $defTextArr[] = $v['label'];
                                break;
                            }
                        }
                        $defText = join(', ', $defTextArr);
                    }
                    // code added by klevu
                    if (!empty($features)) {
                    } else {
                        // default value
                        $html .= '<td class="use-default">';
                        // $html.= '<input id="'.$id.'_inherit" name="'.$namePrefix.'[inherit]" type="checkbox" value="1" class="input-checkbox config-inherit" '.$inherit.' onclick="$(\''.$id.'\').disabled = this.checked">';
                        $html .= '<input id="' . $id . '_inherit" name="' . $namePrefix . '[inherit]" type="checkbox" value="1" class="checkbox config-inherit" ' . $inherit . ' onclick="toggleValueElements(this, Element.previous(this.parentNode))" /> ';
                        $html .= '<label for="' . $id . '_inherit" class="inherit" title="' . htmlspecialchars($defText) . '">' . $checkboxLabel . '</label>';
                        $html .= '</td>';
                    }
                }
                $html .= '<td class="scope-label">';
                if ($element->getScope()) {
                    $html .= $element->getScopeLabel();
                }
                $html .= '</td>';
                // code added by klevu
                if (!empty($features)) {
                    $element->setDisabled(true);
                }
                $html .= '<td class="">';
                if ($element->getHint()) {
                    $html .= '<div class="hint" >';
                    $html .= '<div style="display: none;">' . $element->getHint() . '</div>';
                    $html .= '</div>';
                }
                $html .= '</td>';
                $html .= '</tr>';
                return $html;
            } else {
                $id                 = $element->getHtmlId();
                $useContainerId     = $element->getData('use_container_id');
                $html               = '<tr id="row_' . $id . '">' . '<td class="label"><label for="' . $id . '">' . $element->getLabel() . '</label></td>';
                // $isDefault = !$this->getRequest()->getParam('website') && !$this->getRequest()->getParam('store');
                $isMultiple         = $element->getExtType() === 'multiple';
                // replace [value] with [inherit]
                $namePrefix         = preg_replace('#\[value\](\[\])?$#', '', $element->getName());
                $options            = $element->getValues();
                $addInheritCheckbox = false;
                if ($element->getCanUseWebsiteValue()) {
                    $addInheritCheckbox = true;
                    $checkboxLabel      = Mage::helper('adminhtml')->__('Use Website');
                } elseif ($element->getCanUseDefaultValue()) {
                    $addInheritCheckbox = true;
                    $checkboxLabel      = Mage::helper('adminhtml')->__('Use Default');
                }
                if ($addInheritCheckbox) {
                    $inherit = $element->getInherit() == 1 ? 'checked="checked"' : '';
                    if ($inherit) {
                        $element->setDisabled(true);
                    }
                }
                $html .= '<td class="value">';
                $html .= $this->_getElementHtml($element);
                if ($element->getComment()) {
                    $html .= '<p class="note"><span>' . $element->getComment() . '</span></p>';
                }
                $html .= '</td>';
                if ($addInheritCheckbox) {
                    $defText = $element->getDefaultValue();
                    if ($options) {
                        $defTextArr = array();
                        foreach ($options as $k => $v) {
                            if ($isMultiple) {
                                if (is_array($v['value']) && in_array($k, $v['value'])) {
                                    $defTextArr[] = $v['label'];
                                }
                            } elseif ($v['value'] == $defText) {
                                $defTextArr[] = $v['label'];
                                break;
                            }
                        }
                        $defText = join(', ', $defTextArr);
                    }
                    // default value
                    $html .= '<td class="use-default">';
                    // $html.= '<input id="'.$id.'_inherit" name="'.$namePrefix.'[inherit]" type="checkbox" value="1" class="input-checkbox config-inherit" '.$inherit.' onclick="$(\''.$id.'\').disabled = this.checked">';
                    $html .= '<input id="' . $id . '_inherit" name="' . $namePrefix . '[inherit]" type="checkbox" value="1" class="checkbox config-inherit" ' . $inherit . ' onclick="toggleValueElements(this, Element.previous(this.parentNode))" /> ';
                    $html .= '<label for="' . $id . '_inherit" class="inherit" title="' . htmlspecialchars($defText) . '">' . $checkboxLabel . '</label>';
                    $html .= '</td>';
                }
                $html .= '<td class="scope-label">';
                if ($element->getScope()) {
                    $html .= $element->getScopeLabel();
                }
                $html .= '</td>';
                $html .= '<td class="">';
                if ($element->getHint()) {
                    $html .= '<div class="hint" >';
                    $html .= '<div style="display: none;">' . $element->getHint() . '</div>';
                    $html .= '</div>';
                }
                $html .= '</td>';
                $html .= '</tr>';
                return $html;
            }
        } else {
            if (Mage::app()->getRequest()->getParam('section') == "klevu_search") {
                $id           = $element->getHtmlId();
                $feature_data = Mage::helper('klevu_search/config')->getFeaturesUpdate($element->getHtmlId());
                
                
                if (!empty($feature_data)) {
                    $style        = 'class="klevu-disabled"';
                    $upgrade_text = '';
                    if (!empty($feature_data['upgrade_message']) || !empty($feature_data['upgrade_label'])) {
                        $upgrade_text .= "<div class='klevu-upgrade-block'>";
                        if (!empty($feature_data['upgrade_message'])) {
                            $upgrade_text .= $feature_data['upgrade_message'];
                        }
                        if (!empty($feature_data['upgrade_label'])) {
                            $upgrade_text .= "<br/><button type='button' onClick=upgradeLink('" . $feature_data["upgrade_url"] . "')>" . $feature_data['upgrade_label'] . "</button>";
                        }
                        $upgrade_text .= "</div>";
                    }
                } else {
                    $style        = '';
                    $upgrade_text = '';
                }
                
                $html               = '<td class="label"><label for="' . $id . '" ' . $style . '>' . $element->getLabel() . '</label>' . $upgrade_text . '</td>';
                // $isDefault = !$this->getRequest()->getParam('website') && !$this->getRequest()->getParam('store');
                $isMultiple         = $element->getExtType() === 'multiple';
                // replace [value] with [inherit]
                $namePrefix         = preg_replace('#\[value\](\[\])?$#', '', $element->getName());
                $options            = $element->getValues();
                $addInheritCheckbox = false;
                if ($element->getCanUseWebsiteValue()) {
                    $addInheritCheckbox = true;
                    $checkboxLabel      = $this->__('Use Website');
                } elseif ($element->getCanUseDefaultValue()) {
                    $addInheritCheckbox = true;
                    $checkboxLabel      = $this->__('Use Default');
                }
                if ($addInheritCheckbox) {
                    $inherit = $element->getInherit() == 1 ? 'checked="checked"' : '';
                    if ($inherit) {
                        $element->setDisabled(true);
                    }
                }
                if (!empty($feature_data)) {
                    $element->setDisabled(true);
                    $element->setValue(0);
                }
                if ($element->getTooltip()) {
                    $html .= '<td class="value with-tooltip">';
                    $html .= $this->_getElementHtml($element);
                    $html .= '<div class="field-tooltip"><div>' . $element->getTooltip() . '</div></div>';
                } else {
                    $html .= '<td class="value">';
                    $html .= $this->_getElementHtml($element);
                }
                ;
                if ($element->getComment()) {
                    $html .= '<p class="note"><span>' . $element->getComment() . '</span></p>';
                }
                $html .= '</td>';
                if ($addInheritCheckbox) {
                    $defText = $element->getDefaultValue();
                    if ($options) {
                        $defTextArr = array();
                        foreach ($options as $k => $v) {
                            if ($isMultiple) {
                                if (is_array($v['value']) && in_array($k, $v['value'])) {
                                    $defTextArr[] = $v['label'];
                                }
                            } elseif (isset($v['value'])) {
                                if ($v['value'] == $defText) {
                                    $defTextArr[] = $v['label'];
                                    break;
                                }
                            } elseif (!is_array($v)) {
                                if ($k == $defText) {
                                    $defTextArr[] = $v;
                                    break;
                                }
                            }
                        }
                        $defText = join(', ', $defTextArr);
                    }
                    if (!empty($feature_data)) {
                    } else {
                        // default value
                        $html .= '<td class="use-default">';
                        $html .= '<input id="' . $id . '_inherit" name="' . $namePrefix . '[inherit]" type="checkbox" value="1" class="checkbox config-inherit" ' . $inherit . ' onclick="toggleValueElements(this, Element.previous(this.parentNode))" /> ';
                        $html .= '<label for="' . $id . '_inherit" class="inherit" title="' . htmlspecialchars($defText) . '">' . $checkboxLabel . '</label>';
                        $html .= '</td>';
                    }
                }
                $html .= '<td class="scope-label">';
                if ($element->getScope()) {
                    $html .= $element->getScopeLabel();
                }
                $html .= '</td>';
                if (!empty($feature_data)) {
                    $element->setDisabled(true);
                }
                $html .= '<td class="">';
                if ($element->getHint()) {
                    $html .= '<div class="hint" >';
                    $html .= '<div style="display: none;">' . $element->getHint() . '</div>';
                    $html .= '</div>';
                }
                $html .= '</td>';
                return $this->_decorateRowHtml($element, $html);
            } else {
                $id                 = $element->getHtmlId();
                $html               = '<td class="label"><label for="' . $id . '">' . $element->getLabel() . '</label></td>';
                // $isDefault = !$this->getRequest()->getParam('website') && !$this->getRequest()->getParam('store');
                $isMultiple         = $element->getExtType() === 'multiple';
                // replace [value] with [inherit]
                $namePrefix         = preg_replace('#\[value\](\[\])?$#', '', $element->getName());
                $options            = $element->getValues();
                $addInheritCheckbox = false;
                if ($element->getCanUseWebsiteValue()) {
                    $addInheritCheckbox = true;
                    $checkboxLabel      = $this->__('Use Website');
                } elseif ($element->getCanUseDefaultValue()) {
                    $addInheritCheckbox = true;
                    $checkboxLabel      = $this->__('Use Default');
                }
                if ($addInheritCheckbox) {
                    $inherit = $element->getInherit() == 1 ? 'checked="checked"' : '';
                    if ($inherit) {
                        $element->setDisabled(true);
                    }
                }
                if ($element->getTooltip()) {
                    $html .= '<td class="value with-tooltip">';
                    $html .= $this->_getElementHtml($element);
                    $html .= '<div class="field-tooltip"><div>' . $element->getTooltip() . '</div></div>';
                } else {
                    $html .= '<td class="value">';
                    $html .= $this->_getElementHtml($element);
                }
                ;
                if ($element->getComment()) {
                    $html .= '<p class="note"><span>' . $element->getComment() . '</span></p>';
                }
                $html .= '</td>';
                if ($addInheritCheckbox) {
                    $defText = $element->getDefaultValue();
                    if ($options) {
                        $defTextArr = array();
                        foreach ($options as $k => $v) {
                            if ($isMultiple) {
                                if (is_array($v['value']) && in_array($k, $v['value'])) {
                                    $defTextArr[] = $v['label'];
                                }
                            } elseif (isset($v['value'])) {
                                if ($v['value'] == $defText) {
                                    $defTextArr[] = $v['label'];
                                    break;
                                }
                            } elseif (!is_array($v)) {
                                if ($k == $defText) {
                                    $defTextArr[] = $v;
                                    break;
                                }
                            }
                        }
                        $defText = join(', ', $defTextArr);
                    }
                    // default value
                    $html .= '<td class="use-default">';
                    $html .= '<input id="' . $id . '_inherit" name="' . $namePrefix . '[inherit]" type="checkbox" value="1" class="checkbox config-inherit" ' . $inherit . ' onclick="toggleValueElements(this, Element.previous(this.parentNode))" /> ';
                    $html .= '<label for="' . $id . '_inherit" class="inherit" title="' . htmlspecialchars($defText) . '">' . $checkboxLabel . '</label>';
                    $html .= '</td>';
                }
                $html .= '<td class="scope-label">';
                if ($element->getScope()) {
                    $html .= $element->getScopeLabel();
                }
                $html .= '</td>';
                $html .= '<td class="">';
                if ($element->getHint()) {
                    $html .= '<div class="hint" >';
                    $html .= '<div style="display: none;">' . $element->getHint() . '</div>';
                    $html .= '</div>';
                }
                $html .= '</td>';
                return $this->_decorateRowHtml($element, $html);
            }
        }
    }
    
}
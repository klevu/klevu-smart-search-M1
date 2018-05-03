<?php
class Klevu_Content_Block_Adminhtml_Form_Cmspages extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $selectPages = array();
    
    public function __construct()
    {
        $this->addColumn(
            'cmspages', array(
            'label' => Mage::helper('adminhtml')->__('CMS Pages'),
            'renderer'=> $this->getRenderer('cmspages'),
            )
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Exclude CMS Pages');
        parent::__construct();
        $this->setTemplate('klevu/search/system/config/form/field/array.phtml');

    }
    /**
     * Get all pages in the store.
     *
     * @param int $columnId.
     *
     * @return $selectPages
     */
    protected function getRenderer($columnId) 
    {
        if (!array_key_exists($columnId, $this->selectPages) || !$this->selectPages[$columnId]) {
            $cmsOptions = array();
            switch($columnId) {
                case 'cmspages':
                    $cms_pages = Mage::getModel('cms/page')->getCollection()->addFieldToSelect(array("page_id","title"))->addFieldToFilter('is_active', 1);
                    $page_ids = $cms_pages->getData();
                    foreach ($page_ids as $id) {
                        $cmsOptions[$id['page_id']] = $id['title'];
                    }
                    break;
                default:
            }

            $selectPage = Mage::app()->getLayout()->createBlock('content/adminhtml_form_system_config_field_select')->setIsRenderToJsTemplate(true);
            $selectPage->setOptions($cmsOptions);
            $selectPage->setExtraParams('style="width:200px;"');
            $this->selectPages[$columnId] = $selectPage;
        }

        return $this->selectPages[$columnId];
    }
    
    protected function _prepareArrayRow(Varien_Object $row)
    {
        $row->setData(
            'option_extra_attr_' . $this->getRenderer('cmspages')->calcOptionHash($row->getCmspages()),
            'selected="selected"'
        );
    }
}

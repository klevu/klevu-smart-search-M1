<?php
class Klevu_Boosting_Block_Adminhtml_Boost_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId("boostGrid");
        $this->setDefaultSort("id");
        $this->setDefaultDir("DESC");
        $this->setSaveParametersInSession(true);
    }
    protected function _prepareCollection()
    {
        $collection = Mage::getModel("boosting/boost")->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    protected function _prepareColumns()
    {
        $this->addColumn("id", array(
            "header" => Mage::helper("boosting")->__("ID") ,
            "align" => "right",
            "width" => "50px",
            "type" => "number",
            "index" => "id",
        ));
        $this->addColumn("name", array(
            "header" => Mage::helper("boosting")->__("Rule Name") ,
            "index" => "name",
        ));
        $this->addColumn('status', array(
            'header' => $this->__('Status') ,
            'align' => 'left',
            'width' => '80px',
            'index' => 'status',
            'type' => 'options',
            'options' => array(
                1 => $this->__('Active') ,
                0 => $this->__('Inactive')
            ) ,
        ));
        return parent::_prepareColumns();
    }
    public function getRowUrl($row)
    {
        return $this->getUrl("*/*/edit", array(
            "id" => $row->getId()
        ));
    }
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        $this->getMassactionBlock()->setUseSelectAll(true);
        $this->getMassactionBlock()->addItem('remove_boost', array(
            'label' => Mage::helper('boosting')->__('Remove Rule') ,
            'url' => $this->getUrl('*/boost/massRemove') ,
            'confirm' => Mage::helper('boosting')->__('Are you sure?')
        ));
        return $this;
    }
}

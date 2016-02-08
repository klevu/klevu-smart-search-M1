<?php
class Klevu_Content_SearchController extends Mage_Core_Controller_Front_Action
{
    public function IndexAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock("head")->setTitle($this->__("Content Search"));
        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
        $breadcrumbs->addCrumb("home", array(
            "label" => $this->__("Home") ,
            "title" => $this->__("Home") ,
            "link" => Mage::getBaseUrl()
        ));
        $breadcrumbs->addCrumb("titlename", array(
            "label" => $this->__("Content Search") ,
            "title" => $this->__("Content Search")
        ));
        $this->renderLayout();
    }
}
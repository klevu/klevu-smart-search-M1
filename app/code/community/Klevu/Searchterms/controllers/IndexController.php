<?php
class Klevu_Searchterms_IndexController extends Mage_Core_Controller_Front_Action
{
    public function IndexAction() 
    {
      
        $this->loadLayout();   
        $this->getLayout()->getBlock("head")->setTitle($this->__("Popular Search Terms"));
            $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
        $breadcrumbs->addCrumb(
            "home", array(
                "label" => $this->__("Home"),
                "title" => $this->__("Home"),
                "link"  => Mage::getBaseUrl()
            )
        );

        $breadcrumbs->addCrumb(
            "titlename", array(
                "label" => $this->__("Popular Search Terms"),
                "title" => $this->__("Popular Search Terms")
            )
        );

        $this->renderLayout(); 
      
    }
}

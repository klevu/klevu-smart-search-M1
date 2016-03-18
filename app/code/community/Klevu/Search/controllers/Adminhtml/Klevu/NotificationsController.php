<?php

class Klevu_Search_Adminhtml_Klevu_NotificationsController extends Mage_Adminhtml_Controller_Action {

    public function dismissAction() {
        $id = intval($this->getRequest()->getParam("id"));

        $notification = Mage::getModel('klevu_search/notification')->load($id);

        if ($notification->getId()) {
            $notification->delete();
        } else {
            Mage::getSingleton("adminhtml/session")->addError("Unable to dismiss Klevu notification as it does not exist.");
        }

        return $this->_redirectReferer($this->getUrl("adminhtml/dashboard"));
    }
	
	protected function _isAllowed()
    {
        return true;
    }
}

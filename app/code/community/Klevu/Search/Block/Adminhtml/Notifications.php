<?php

class Klevu_Search_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{

    /**
     * Return all notifications.
     *
     * @return Klevu_Search_Model_Resource_Notification_Collection
     */
    protected function getNotifications() 
    {
        return Mage::getResourceModel("klevu_search/notification_collection");
    }

    /**
     * Return the URL to dismiss the given notification.
     *
     * @param $notification
     *
     * @return string
     */
    protected function getDismissUrl($notification) 
    {
        return $this->getUrl("adminhtml/klevu_notifications/dismiss", array("id" => $notification->getId()));
    }
}

<?php

class Klevu_Search_Model_Cron extends Varien_Object
{

    public function clearCronCheckNotification() 
    {
        $collection = Mage::getResourceModel("klevu_search/notification_collection");
        $collection->addFieldToFilter("type", array("eq" => "cron_check"));

        foreach ($collection as $notification) {
            $notification->delete();
        }
    }
}

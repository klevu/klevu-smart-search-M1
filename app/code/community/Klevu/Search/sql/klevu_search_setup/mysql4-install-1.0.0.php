<?php
/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

$connection = $installer->getConnection();



$notifications_table = $installer->getTable('klevu_search/notification');

$installer->run("DROP TABLE IF EXISTS `{$notifications_table}`");

$installer->run("
CREATE TABLE `{$notifications_table}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `type` varchar(32) NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

// Add a notification to setup cron and a cron job to clear
// the notification the next time cron runs
$installer->getConnection()->insert($notifications_table, array(
    "type" => "cron_check",
    "message" => Mage::helper("klevu_search")->__('Klevu Search relies on cron for normal operations. Please check that you have Magento cron set up correctly. You can find instructions on how to set up Magento Cron <a target="_blank" href="http://support.klevu.com/knowledgebase/setup-a-cron/">here</a>.')
));

$now = date_create("now")->format("Y-m-d H:i:00");

$schedule = Mage::getModel("cron/schedule");
$schedule
    ->setJobCode("klevu_search_clear_cron_check")
    ->setCreatedAt($now)
    ->setScheduledAt($now)
    ->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)
    ->save();



$product_sync_table = $installer->getTable('klevu_search/product_sync');

// Pre-existing sync data is of no use, so drop the existing
// table before recreating it
$installer->run("DROP TABLE IF EXISTS `{$product_sync_table}`");

$installer->run("
CREATE TABLE `{$product_sync_table}` (
  `product_id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL DEFAULT 0,
  `store_id` smallint(5) unsigned NOT NULL,
  `test_mode` int(1) NOT NULL DEFAULT 0,
  `last_synced_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`, `parent_id`, `store_id`, `test_mode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");



$order_sync_table = $installer->getTable('klevu_search/order_sync');

$installer->run("DROP TABLE IF EXISTS `{$order_sync_table}`");

$installer->run("
CREATE TABLE `{$order_sync_table}` (
  `order_item_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`order_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");



$installer->endSetup();

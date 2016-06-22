<?php
/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();
$klevu_order_sync_table = $installer->getTable('klevu_search/order_sync');
$installer->run("ALTER TABLE `{$klevu_order_sync_table}` ADD `klevu_session_id` VARCHAR(255) NOT NULL , ADD `ip_address` VARCHAR(255) NOT NULL , ADD `date` DATETIME NOT NULL");
$installer->endSetup();

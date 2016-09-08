<?php
/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();
$klevu_order_sync_table = $installer->getTable('klevu_search/order_sync');
$installer->run("ALTER TABLE `{$klevu_order_sync_table}` ADD `email` VARCHAR(255) NOT NULL AFTER `date`");
$installer->endSetup();

<?php
/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();
$core_config_data = $installer->getTable('core_config_data');
$installer->run("INSERT INTO `{$core_config_data}` (`scope`, `scope_id`, `path`, `value`) VALUES ('default', '0', 'klevu_search/general/rating_flag', '0')");
$installer->endSetup();

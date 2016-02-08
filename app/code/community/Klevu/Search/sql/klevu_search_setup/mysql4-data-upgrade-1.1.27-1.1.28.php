<?php
/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();
$core_config_data = $installer->getTable('core_config_data');
$installer->run("UPDATE {$core_config_data} SET `path` = 'klevu_search/attribute_boost/boosting' WHERE path LIKE '%klevu_search/attributes/boosting%'");
$installer->run("UPDATE {$core_config_data} SET `path` = 'klevu_search/add_to_cart/enabledaddtocartfront' WHERE path LIKE '%klevu_search/add_to_cart/enabled%'");
$installer->endSetup();

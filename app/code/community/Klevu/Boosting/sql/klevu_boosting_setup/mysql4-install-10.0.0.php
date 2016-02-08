<?php
$installer = $this;
$installer->startSetup();
$klevu_products_boosting = $installer->getTable('klevu_products_boosting');
$sql=<<<SQLTEXT
CREATE TABLE IF NOT EXISTS `{$klevu_products_boosting}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` tinytext NOT NULL,
  `description` text NOT NULL,
  `status` tinyint(4) NOT NULL,
  `boosting` int(11) NOT NULL,
  `matchingids` text NOT NULL,
  `conditions_serialized` mediumtext NOT NULL,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
SQLTEXT;
$installer->run($sql);
$installer->endSetup();
	 
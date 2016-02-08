<?php
/**
 * Add all of the attributes mapped in "Additional Attributes" section to the
 * "Other Attributes to Index" section
 */

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

$mage_config = Mage::getConfig();
$klevu_config = Mage::helper("klevu_search/config");

foreach (Mage::app()->getStores() as $store) {
    /** @var Mage_Core_Model_Store $store */
    $additional_attributes = $klevu_config->getAdditionalAttributesMap($store);

    if (count($additional_attributes) == 0) {
        continue;
    }

    $other_attributes = $klevu_config->getOtherAttributesToIndex($store);

    foreach ($additional_attributes as $mapping) {
        $other_attributes[] = $mapping["magento_attribute"];
    }

    $other_attributes = array_unique($other_attributes);

    if (count($other_attributes) == 0) {
        continue;
    }

    $other_attributes = implode(",", $other_attributes);

    $scope_id = $mage_config->getNode(sprintf(Klevu_Search_Helper_Config::XML_PATH_STORE_ID, $store->getCode()));
    if ($scope_id !== null) {
        $scope_id = (int) $scope_id;

        $mage_config->saveConfig(Klevu_Search_Helper_Config::XML_PATH_ATTRIBUTES_OTHER, $other_attributes, "stores", $scope_id);
    }
}

$mage_config->reinit();

$installer->endSetup();

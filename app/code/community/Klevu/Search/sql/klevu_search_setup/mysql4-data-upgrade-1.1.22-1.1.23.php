<?php
$installer = $this;
$installer->startSetup();
$product_sync_data_table = $installer->getTable('klevu_search/product_sync');
$installer->run("ALTER TABLE `{$product_sync_data_table}` ADD `type` VARCHAR(255) NOT NULL DEFAULT 'products' AFTER `last_synced_at`");
$installer->run("ALTER TABLE `{$product_sync_data_table}` DROP PRIMARY KEY, ADD PRIMARY KEY(`product_id`,`parent_id`,`store_id`,`test_mode`,`type`)");
try {
    $entity_type = Mage::getSingleton("eav/entity_type")->loadByCode("catalog_product");
    $entity_typeid = $entity_type->getId();
    $attributecollection = Mage::getModel("eav/entity_attribute")->getCollection()->addFieldToFilter("entity_type_id", $entity_typeid)->addFieldToFilter("attribute_code", "rating");
    if (!count($attributecollection)) {
        $attribute = $attributecollection->getFirstItem();
        $data = array();
        $data['id'] = null;
        $data['entity_type_id'] = $entity_typeid;
        $data['attribute_code'] = "rating";
        $data['backend_type'] = "varchar";
        $data['frontend_input'] = "text";
        $data['frontend_label'] = 'Rating';
        $data['default_value_text'] = '0';
        $data['is_global'] = '0';
        $data['is_user_defined'] = '1';
        $attribute->setData($data);
        $attribute->save();
        $resource = Mage::getSingleton('core/resource');
        $read = $resource->getConnection('core_read');
        $write = $resource->getConnection('core_write');
        $entity_type = Mage::getSingleton("eav/entity_type")->loadByCode("catalog_product");
        $entity_typeid = $entity_type->getId();
        $select = $read->select()->from($this->getTable("eav_attribute_set") , array(
            'attribute_set_id'
        ))->where("entity_type_id=?", $entity_typeid);
        $attribute_sets = $read->fetchAll($select);
        foreach($attribute_sets as $attribute_set) {
            $attribute_set_id = $attribute_set['attribute_set_id'];
            $select = $read->select()->from($this->getTable("eav_attribute") , array(
                'attribute_id'
            ))->where("entity_type_id=?", $entity_typeid)->where("attribute_code=?", "rating");
            $attribute = $read->fetchRow($select);
            $attribute_id = $attribute['attribute_id'];
            $select = $read->select()->from($this->getTable("eav_attribute_group") , array(
                'attribute_group_id'
            ))->where("attribute_set_id=?", $attribute_set_id)->where("attribute_group_name=?", "General");
            $attribute_group = $read->fetchRow($select);
            $attribute_group_id = $attribute_group['attribute_group_id'];
            $write->beginTransaction();
            $write->insert($this->getTable("eav_entity_attribute") , array(
                "entity_type_id" => $entity_typeid,
                "attribute_set_id" => $attribute_set_id,
                "attribute_group_id" => $attribute_group_id,
                "attribute_id" => $attribute_id,
                "sort_order" => 5
            ));
            $write->commit();
        }
    }
} catch(Exception $e) {
    echo '<p>Error occurred while trying to add the attribute. Error: ' . $e->getMessage() . '</p>';
}
$installer->endSetup();
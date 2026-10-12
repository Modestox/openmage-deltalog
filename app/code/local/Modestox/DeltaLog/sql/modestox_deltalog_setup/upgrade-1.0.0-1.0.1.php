<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$ruleTableName = $installer->getTable('modestox_deltalog/rule');
$ruleFieldTableName = $installer->getTable('modestox_deltalog/rule_field');

/**
 * 1. Create Rules Table
 */
if (!$installer->getConnection()->isTableExists($ruleTableName)) {
    $ruleTable = $installer
        ->getConnection()
        ->newTable($ruleTableName)
        ->addColumn(
            'rule_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Rule ID',
        )
        ->addColumn(
            'title',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            255,
            ['nullable' => false],
            'Rule Title',
        )
        ->addColumn(
            'entity_type',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            32,
            ['nullable' => false, 'default' => 'flat'],
            'Entity Type (flat or eav)',
        )
        ->addColumn(
            'table_name',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            64,
            ['nullable' => false],
            'Main DB Table Name',
        )
        ->addColumn(
            'model_alias',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            128,
            ['nullable' => true, 'default' => null],
            'Magento Model Alias',
        )
        ->addColumn(
            'title_field',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            64,
            ['nullable' => true, 'default' => null],
            'Field Name for Entity Title',
        )
        ->addColumn(
            'edit_url_route',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            128,
            ['nullable' => true, 'default' => null],
            'Admin Edit Route URL',
        )
        ->addColumn(
            'is_active',
            Varien_Db_Ddl_Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 1],
            'Is Rule Active',
        )
        ->addColumn(
            'created_at',
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT],
            'Created At',
        )
        ->addColumn(
            'updated_at',
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT_UPDATE],
            'Updated At',
        )
        ->addIndex(
            $installer->getIdxName($ruleTableName, ['table_name', 'is_active']),
            ['table_name', 'is_active'],
        )
        ->setComment('Modestox Delta Log Rules Table');

    $installer->getConnection()->createTable($ruleTable);
}

/**
 * 2. Create Rule Fields Table
 */
if (!$installer->getConnection()->isTableExists($ruleFieldTableName)) {
    $ruleFieldTable = $installer
        ->getConnection()
        ->newTable($ruleFieldTableName)
        ->addColumn(
            'field_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Field ID',
        )
        ->addColumn(
            'rule_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Parent Rule ID',
        )
        ->addColumn(
            'field_code',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            64,
            ['nullable' => false],
            'Field DB Code or Attribute Code',
        )
        ->addColumn(
            'field_label',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            128,
            ['nullable' => false],
            'Human-readable Label',
        )
        ->addColumn(
            'value_type',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            32,
            ['nullable' => false, 'default' => 'text'],
            'Data Value Type (text, date, select, multiselect, boolean)',
        )
        ->addColumn(
            'source_model',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            255,
            ['nullable' => true, 'default' => null],
            'Source Model Class or Alias',
        )
        ->addColumn(
            'helper_method',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            255,
            ['nullable' => true, 'default' => null],
            'Helper Method Call',
        )
        ->addColumn(
            'custom_options',
            Varien_Db_Ddl_Table::TYPE_TEXT,
            '64k',
            ['nullable' => true, 'default' => null],
            'JSON Encoded Options Map',
        )
        ->addColumn(
            'is_active',
            Varien_Db_Ddl_Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 1],
            'Is Field Active',
        )
        ->addIndex(
            $installer->getIdxName($ruleFieldTableName, ['rule_id', 'field_code']),
            ['rule_id', 'field_code'],
        )
        ->addForeignKey(
            $installer->getFkName($ruleFieldTableName, 'rule_id', $ruleTableName, 'rule_id'),
            'rule_id',
            $ruleTableName,
            'rule_id',
            Varien_Db_Ddl_Table::ACTION_CASCADE,
            Varien_Db_Ddl_Table::ACTION_CASCADE,
        )
        ->setComment('Modestox Delta Log Rule Tracked Fields Table');

    $installer->getConnection()->createTable($ruleFieldTable);
}

$installer->endSetup();
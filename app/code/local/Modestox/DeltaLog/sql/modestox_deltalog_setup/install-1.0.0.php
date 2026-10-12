<?php
/**
 * Modestox CMS - E-commerce Platform
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   AGPL-3.0-or-later
 * @link      https://github.com/Modestox/modestox
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('modestox_deltalog/entry');

if (!$installer->getConnection()->isTableExists($tableName)) {
    $table = $installer->getConnection()
        ->newTable($tableName)
        ->addColumn(
            'log_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array('identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true),
            'Log ID'
        )
        ->addColumn(
            'user_name',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            255,
            array('nullable' => false, 'default' => 'System'),
            'User Name'
        )
        ->addColumn(
            'entity_title',
            Varien_Db_Ddl_Table::TYPE_TEXT,
            255,
            array('nullable' => true, 'default' => null),
            'Entity Title Value'
        )
        ->addColumn(
            'entity_info',
            Varien_Db_Ddl_Table::TYPE_TEXT,
            255,
            array('nullable' => true, 'default' => null),
            'Formatted Entity Info'
        )
        ->addColumn(
            'ip_address',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            45,
            array('nullable' => false, 'default' => '0.0.0.0'),
            'IP Address'
        )
        ->addColumn(
            'table_name',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            64,
            array('nullable' => false),
            'Table Name'
        )
        ->addColumn(
            'entity_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array('unsigned' => true, 'nullable' => false),
            'Entity ID'
        )
        ->addColumn(
            'field_code',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            64,
            array('nullable' => false),
            'Field Code'
        )
        ->addColumn(
            'field_label',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            128,
            array('nullable' => false),
            'Field Label'
        )
        ->addColumn(
            'old_value',
            Varien_Db_Ddl_Table::TYPE_TEXT,
            '64k',
            array('nullable' => true),
            'Old Value'
        )
        ->addColumn(
            'new_value',
            Varien_Db_Ddl_Table::TYPE_TEXT,
            '64k',
            array('nullable' => true),
            'New Value'
        )
        ->addColumn(
            'created_at',
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            array('nullable' => false, 'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT),
            'Created At'
        )
        ->addIndex(
            $installer->getIdxName($tableName, array('table_name', 'field_code', 'entity_id')),
            array('table_name', 'field_code', 'entity_id')
        )
        ->setComment('Modestox Delta Log Entry Table');

    $installer->getConnection()->createTable($table);
}

$installer->endSetup();
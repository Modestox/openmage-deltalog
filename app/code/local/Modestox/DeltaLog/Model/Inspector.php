<?php
/**
 * Modestox CMS - E-commerce Platform
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   AGPL-3.0-or-later
 * @link      https://github.com/Modestox/modestox
 */

class Modestox_DeltaLog_Model_Inspector
{
    /**
     * Get list of database columns for flat table
     *
     * @param string $tableName
     * @return array
     */
    public function getTableColumns(string $tableName): array
    {
        if (empty($tableName)) {
            return [];
        }

        $resource = Mage::getSingleton('core/resource');
        $adapter = $resource->getConnection('core_read');
        $realTableName = $resource->getTableName($tableName);

        if (!$adapter->isTableExists($realTableName)) {
            if ($adapter->isTableExists($tableName)) {
                $realTableName = $tableName;
            } else {
                return [];
            }
        }

        $columns = $adapter->describeTable($realTableName);
        $result = [];

        foreach ($columns as $columnName => $columnInfo) {
            $result[] = [
                'code'  => $columnName,
                'label' => ucwords(str_replace('_', ' ', $columnName)),
                'type'  => $this->_mapDbTypeToValueType((string)$columnInfo['DATA_TYPE']),
            ];
        }

        return $result;
    }

    /**
     * Get list of EAV attributes for entity type
     *
     * @param string $entityTypeCode
     * @return array
     */
    public function getEavAttributes(string $entityTypeCode): array
    {
        if (empty($entityTypeCode)) {
            return [];
        }

        $cleanTypeCode = str_replace('_entity', '', strtolower(trim($entityTypeCode)));

        /** @var Mage_Eav_Model_Entity_Type $entityType */
        $entityType = Mage::getModel('eav/entity_type')->loadByCode($cleanTypeCode);
        if (!$entityType->getId()) {
            return [];
        }

        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Collection $collection */
        $collection = Mage::getResourceModel('eav/entity_attribute_collection');
        $collection->setEntityTypeFilter($entityType->getId());

        $result = [];
        /** @var Mage_Eav_Model_Entity_Attribute $attribute */
        foreach ($collection as $attribute) {
            $code = $attribute->getAttributeCode();
            $label = $attribute->getFrontendLabel() ?: $code;

            $result[] = [
                'code'         => $code,
                'label'        => $label,
                'type'         => $attribute->getBackendType() === 'datetime' ? 'date' : 'text',
                'source_model' => $attribute->getSourceModel() ?: null,
            ];
        }

        return $result;
    }

    /**
     * Map database data types to internal delta value types
     *
     * @param string $dbType
     * @return string
     */
    protected function _mapDbTypeToValueType(string $dbType): string
    {
        $dbType = strtolower($dbType);

        if (in_array($dbType, ['date', 'datetime', 'timestamp'])) {
            return 'date';
        }

        if (in_array($dbType, ['tinyint', 'boolean'])) {
            return 'boolean';
        }

        return 'text';
    }
}
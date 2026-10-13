<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Model_Processor
{
    /**
     * Helper instance
     *
     * @var Modestox_DeltaLog_Helper_Data|null
     */
    protected ?Modestox_DeltaLog_Helper_Data $_helper = null;

    /**
     * Last value written to the log per "table|id|field" within this request.
     * Model::getOrigData() is only refreshed by load(), so when the same object is saved twice
     * in one request the second save would otherwise log the first change again.
     *
     * @var array<string, string>
     */
    protected static array $_baseline = [];

    /**
     * Get module helper
     *
     * @return Modestox_DeltaLog_Helper_Data
     */
    protected function _getHelper(): Modestox_DeltaLog_Helper_Data
    {
        if (is_null($this->_helper)) {
            $this->_helper = Mage::helper('modestox_deltalog');
        }

        return $this->_helper;
    }

    /**
     * Process entity model changes and persist detected deltas
     *
     * @param Mage_Core_Model_Abstract $model
     * @return void
     */
    public function processModelChanges(Mage_Core_Model_Abstract $model): void
    {
        $tableName = $this->resolveTableName($model);

        /** @var Modestox_DeltaLog_Model_Compiler $compiler */
        $compiler = Mage::getModel('modestox_deltalog/compiler');
        $entityConfig = $compiler->getCompiledRules()[$tableName] ?? null;

        if (empty($entityConfig['fields'])) {
            return;
        }

        $this->_getHelper()->debug(
            sprintf(
                'match: table "%s", id %s, %d tracked field(s)',
                $tableName,
                $model->getId(),
                count($entityConfig['fields'])
            )
        );

        $entityId = (int)$model->getId();
        $entityTitle = isset($entityConfig['title_field']) && $entityConfig['title_field']
            ? $this->valueToString($model->getData($entityConfig['title_field']))
            : null;

        $formattedEntityInfo = $this->buildFormattedEntityInfo($entityId, $entityTitle);
        $userName = $this->resolveCurrentUserName();
        $ipAddress = Mage::helper('core/http')->getRemoteAddr() ?: '0.0.0.0';
        $helper = $this->_getHelper();

        $rowsToInsert = [];
        $fields = $entityConfig['fields'];
        $origData = $model->getOrigData();

        foreach ($fields as $fieldCode => $fieldData) {
            if (!$model->hasData($fieldCode)) {
                continue;
            }

            $translatedLabel = $helper->__($fieldData['label']);
            $newValue = $model->getData($fieldCode);
            $oldValue = is_array($origData) && array_key_exists($fieldCode, $origData)
                ? $origData[$fieldCode]
                : null;

            $baselineKey = $tableName . '|' . $entityId . '|' . $fieldCode;
            $oldString = self::$_baseline[$baselineKey] ?? $this->valueToString($oldValue);
            $newString = $this->valueToString($newValue);

            $this->_getHelper()->debug(sprintf('CHECK [%s]: oldString="%s" (type: %s), newString="%s" (type: %s)', $fieldCode, $oldString, gettype($oldValue), $newString, gettype($newValue)));

            if ($this->hasFieldValueChanged($oldString, $newString, $fieldData)) {
                self::$_baseline[$baselineKey] = $newString;
                $rowsToInsert[] = [
                    'user_name'    => $userName,
                    'ip_address'   => $ipAddress,
                    'table_name'   => $tableName,
                    'entity_id'    => $entityId,
                    'entity_title' => $entityTitle,
                    'entity_info'  => $formattedEntityInfo,
                    //'edit_url_route' => $entityConfig['edit_url_route'] ?? null,
                    'field_code'   => $fieldCode,
                    'field_label'  => $translatedLabel,
                    'old_value'    => $this->transformValueToDisplayLabel($fieldData, $oldString, $fieldCode, $tableName),
                    'new_value'    => $this->transformValueToDisplayLabel($fieldData, $newString, $fieldCode, $tableName),
                    'created_at'   => Varien_Date::now(),
                ];
            }
        }

        if (!empty($rowsToInsert)) {
            $this->saveDeltaEntries($rowsToInsert);
        }
    }

    /**
     * Resolve database main table name for given model
     *
     * @param Mage_Core_Model_Abstract $model
     * @return string
     */
    protected function resolveTableName(Mage_Core_Model_Abstract $model): string
    {
        $resource = $model->getResource();
        if (method_exists($resource, 'getMainTable')) {
            $table = $resource->getMainTable();
        } elseif (method_exists($resource, 'getEntityTable')) {
            $table = $resource->getEntityTable();
        } else {
            $table = (string)$model->getResourceName();
        }

        return $this->_getHelper()->stripTablePrefix((string)$table);
    }

    /**
     * Scalars are trimmed, arrays/objects are JSON encoded.
     *
     * @param mixed $value
     * @return string
     */
    protected function valueToString($value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_scalar($value)) {
            return trim((string)$value);
        }

        return (string)json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    /**
     * Only strips meaningless trailing zeros of decimals.
     *
     * @param string $val
     * @return string
     */
    protected function normalizeDecimalString(string $val): string
    {
        if (preg_match('/^-?\d+\.\d+$/', $val)) {
            $val = rtrim(rtrim($val, '0'), '.');

            return ($val === '-0' || $val === '') ? '0' : $val;
        }

        return $val;
    }

    /**
     * Build standardized entity info string (Title + ID)
     *
     * @param int $entityId
     * @param string|null $entityTitle
     * @return string
     */
    protected function buildFormattedEntityInfo(int $entityId, ?string $entityTitle): string
    {
        if ($entityTitle !== null && $entityTitle !== '') {
            return sprintf('%s (%d)', $entityTitle, $entityId);
        }

        return sprintf('ID: %d', $entityId);
    }

    /**
     * Check if old and new field values are distinct
     *
     * @param string $oldVal
     * @param string $newVal
     * @param array $fieldData
     * @return bool
     */
    protected function hasFieldValueChanged(string $oldVal, string $newVal, array $fieldData): bool
    {
        if ($oldVal === $newVal) {
            return false;
        }

        if ($this->normalizeDecimalString($oldVal) === $this->normalizeDecimalString($newVal)) {
            return false;
        }

        $valueType = $fieldData['value_type'] ?? 'text';
        $isDateType = ($valueType === 'date');

        if ($isDateType || $this->matchesDateFormatPattern($oldVal) || $this->matchesDateFormatPattern($newVal)) {
            $oldTime = strtotime($oldVal);
            $newTime = strtotime($newVal);

            if ($oldTime !== false && $newTime !== false) {
                if (date('Y-m-d', $oldTime) === date('Y-m-d', $newTime)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Transform raw column value into human-readable label using source models, options or helper methods
     *
     * @param array $fieldData
     * @param string|null $value
     * @param string $fieldCode
     * @param string $tableName
     * @return string|null
     */
    protected function transformValueToDisplayLabel(array $fieldData, ?string $value, string $fieldCode = '', string $tableName = ''): ?string
    {
        if ($value === '' || $value === null) {
            return '—';
        }

        $helper = $this->_getHelper();
        $valueType = $fieldData['value_type'] ?? 'text';

        if ($valueType === 'date' || $this->matchesDateFormatPattern($value)) {
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return date('d.m.Y', $timestamp);
            }
        }

        $options = $this->loadFieldOptionDictionary($fieldData, $fieldCode, $tableName);

        if (!empty($options)) {
            $strValue = (string)$value;

            if (is_array($value) || strpos($strValue, ',') !== false) {
                $valArray = is_array($value) ? $value : explode(',', $strValue);
                $formattedValues = [];

                foreach ($valArray as $valItem) {
                    $valItem = trim((string)$valItem);
                    if (array_key_exists($valItem, $options)) {
                        $formattedValues[] = sprintf('%s [%s]', $helper->__((string)$options[$valItem]), $valItem);
                    } else {
                        $formattedValues[] = $valItem;
                    }
                }

                return implode(', ', $formattedValues);
            }

            if (array_key_exists($strValue, $options)) {
                return sprintf('%s [%s]', $helper->__((string)$options[$strValue]), $strValue);
            }
        }

        return $value;
    }

    /**
     * Load options dictionary safely without instantiating eav/entity_attribute_source_table directly
     *
     * @param array $fieldData
     * @param string $fieldCode
     * @param string $tableName
     * @return array
     */
    protected function loadFieldOptionDictionary(array $fieldData, string $fieldCode = '', string $tableName = ''): array
    {
        $options = [];
        $helper = $this->_getHelper();
        $code = !empty($fieldData['field_code']) ? (string)$fieldData['field_code'] : $fieldCode;

        if (!empty($fieldData['custom_options'])) {
            foreach ($fieldData['custom_options'] as $optKey => $optVal) {
                $cleanKey = str_replace('v_', '', (string)$optKey);
                $options[$cleanKey] = $helper->__((string)$optVal);
            }
        } elseif (!empty($fieldData['source_model'])) {
            $sourceClass = (string)$fieldData['source_model'];

            try {
                // 1. Для системных EAV таблиц (eav/entity_attribute_source_table)
                if ($sourceClass === 'eav/entity_attribute_source_table') {
                    if (!empty($code)) {
                        $entityTypeCode = !empty($tableName) ? str_replace('_entity', '', $tableName) : 'customer';

                        /** @var Mage_Eav_Model_Entity_Attribute $attribute */
                        $attribute = Mage::getResourceModel('eav/entity_attribute_collection')
                            ->setCodeFilter($code)
                            ->setEntityTypeFilter(Mage::getModel('eav/entity_type')->loadByCode($entityTypeCode)->getId())
                            ->getFirstItem();

                        if ($attribute && $attribute->getId()) {
                            // ИСПРАВЛЕННЫЙ АЛИАС: eav/entity_attribute_option_collection
                            $optionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection');

                            if ($optionCollection) {
                                $optionCollection->setAttributeFilter($attribute->getId())
                                    ->setStoreFilter(Mage::app()->getStore()->getId());

                                foreach ($optionCollection as $option) {
                                    $options[(string)$option->getOptionId()] = (string)$option->getValue();
                                }
                            }
                        }
                    }
                    return $options;
                }

                // 2. Для автономных моделей (например, customer/customer_attribute_source_group)
                $sourceModel = Mage::getModel($sourceClass) ?: Mage::getResourceModel($sourceClass);

                if ($sourceModel) {
                    if (method_exists($sourceModel, 'getAllOptions')) {
                        $rawOptions = $sourceModel->getAllOptions(false);
                        if (is_array($rawOptions)) {
                            foreach ($rawOptions as $opt) {
                                if (isset($opt['value'], $opt['label']) && $opt['value'] !== '' && $opt['value'] !== null) {
                                    $options[(string)$opt['value']] = (string)$opt['label'];
                                }
                            }
                        }
                    } elseif (method_exists($sourceModel, 'getOptionsToArray')) {
                        $options = $sourceModel->getOptionsToArray();
                    } elseif (method_exists($sourceModel, 'toOptionArray')) {
                        $rawOptions = $sourceModel->toOptionArray();
                        if (is_array($rawOptions)) {
                            foreach ($rawOptions as $opt) {
                                if (isset($opt['value'], $opt['label'])) {
                                    $options[(string)$opt['value']] = (string)$opt['label'];
                                }
                            }
                        }
                    } elseif (method_exists($sourceModel, 'toOptionHash')) {
                        $options = $sourceModel->toOptionHash();
                    }
                }
            } catch (Throwable $e) {
                $helper->debug('Error loading option dictionary for field ' . ($code ?: 'unknown') . ': ' . $e->getMessage());
            }
        } elseif (!empty($fieldData['helper_method'])) {
            $parts = explode('::', (string)$fieldData['helper_method']);
            if (count($parts) === 2) {
                [$helperAlias, $method] = $parts;
                $helperObj = Mage::helper($helperAlias);
                if ($helperObj && method_exists($helperObj, $method)) {
                    $options = call_user_func([$helperObj, $method]);
                }
            }
        }

        return $options;
    }

    /**
     * Detect if value matches standard date/datetime formats
     *
     * @param string $val
     * @return bool
     */
    protected function matchesDateFormatPattern(string $val): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}/', $val) || preg_match('/^\d{2}[\.\/]\d{2}[\.\/]\d{4}/', $val);
    }

    /**
     * Resolve active user identity (Admin or Customer)
     *
     * @return string
     */
    protected function resolveCurrentUserName(): string
    {
        if (Mage::app()->getStore()->isAdmin()) {
            $adminUser = Mage::getSingleton('admin/session')->getUser();
            if ($adminUser && $adminUser->getId()) {
                return $adminUser->getUsername() . ' (Admin)';
            }
        }

        return 'System / CLI';
    }

    /**
     * Perform bulk insert of generated delta log entries into database
     *
     * @param array $rowsToInsert
     * @return void
     */
    protected function saveDeltaEntries(array $rowsToInsert): void
    {
        $resource = Mage::getSingleton('core/resource');
        $writeAdapter = $resource->getConnection('core_write');
        $writeAdapter->insertMultiple(
            $resource->getTableName('modestox_deltalog/entry'),
            $rowsToInsert
        );
    }
}
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
     * Unique sentinel marker for explicit NULL representation in internal baseline array.
     */
    protected const NULL_SENTINEL = "\0__DELTALOG_NULL__\0";

    /**
     * Helper instance.
     *
     * @var Modestox_DeltaLog_Helper_Data|null
     */
    protected ?Modestox_DeltaLog_Helper_Data $_helper = null;

    /**
     * Baseline cache.
     *
     * @var array<string, mixed>
     */
    protected static array $_baseline = [];

    /**
     * Get module helper.
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
     * Process entity model changes.
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

        $entityId = (int)$model->getId();
        if (!$entityId) {
            return;
        }

        $entityTitle = isset($entityConfig['title_field']) && $entityConfig['title_field']
            ? $this->valueToString($model->getData($entityConfig['title_field']))
            : null;

        $formattedEntityInfo = $this->buildFormattedEntityInfo($entityId, $entityTitle);
        $userName = $this->resolveCurrentUserName();
        $ipAddress = Mage::helper('core/http')->getRemoteAddr() ?: '0.0.0.0';
        $helper = $this->_getHelper();

        $rowsToInsert = [];
        $pendingBaseline = [];
        $fields = $entityConfig['fields'];
        $origData = $model->getOrigData();

        $isNewObject = $model->isObjectNew();

        foreach ($fields as $fieldCode => $fieldData) {
            if (!$model->hasData($fieldCode)) {
                continue;
            }

            $translatedLabel = $helper->__($fieldData['label']);
            $newValue = $model->getData($fieldCode);

            $hasOrigKey = is_array($origData) && array_key_exists($fieldCode, $origData);
            $oldValue = $hasOrigKey ? $origData[$fieldCode] : null;

            $baselineKey = $tableName . '|' . $entityId . '|' . $fieldCode;

            $newBaselineVal = $newValue === null ? self::NULL_SENTINEL : $this->valueToString($newValue);

            if (array_key_exists($baselineKey, self::$_baseline) && self::$_baseline[$baselineKey] === $newBaselineVal) {
                continue;
            }

            $oldBaselineVal = self::$_baseline[$baselineKey]
                ?? ($hasOrigKey ? ($oldValue === null ? self::NULL_SENTINEL : $this->valueToString($oldValue)) : self::NULL_SENTINEL);

            if ($isNewObject && !$hasOrigKey && ($newValue === null || $newValue === '')) {
                continue;
            }

            if ($this->hasFieldValueChanged($oldValue, $newValue, $oldBaselineVal, $newBaselineVal, $fieldData)) {
                $pendingBaseline[$baselineKey] = $newBaselineVal;

                $rowsToInsert[] = [
                    'user_name'    => $userName,
                    'ip_address'   => $ipAddress,
                    'table_name'   => $tableName,
                    'entity_id'    => $entityId,
                    'entity_title' => $entityTitle,
                    'entity_info'  => $formattedEntityInfo,
                    'field_code'   => $fieldCode,
                    'field_label'  => $translatedLabel,
                    'old_value'    => ($isNewObject && !$hasOrigKey) ? '—' : $this->transformValueToDisplayLabel(
                        $fieldData,
                        $oldValue,
                        $fieldCode,
                        $tableName
                    ),
                    'new_value'    => $this->transformValueToDisplayLabel(
                        $fieldData,
                        $newValue,
                        $fieldCode,
                        $tableName
                    ),
                    'created_at'   => Varien_Date::now(),
                ];
            }
        }

        if (!empty($rowsToInsert)) {
            $this->saveDeltaEntries($rowsToInsert);

            foreach ($pendingBaseline as $key => $val) {
                self::$_baseline[$key] = $val;
            }
        }
    }

    /**
     * Resolve table name for model.
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
     * Convert value to string representation preserving whitespaces and strict scalar types.
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
            return (string)$value;
        }

        return (string)json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    /**
     * Normalize decimal string.
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
     * Build formatted entity info string.
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
     * Check if field value changed.
     *
     * @param mixed $oldRaw
     * @param mixed $newRaw
     * @param mixed $oldBaseline
     * @param mixed $newBaseline
     * @param array $fieldData
     * @return bool
     */
    protected function hasFieldValueChanged($oldRaw, $newRaw, $oldBaseline, $newBaseline, array $fieldData): bool
    {
        if (is_array($oldRaw) || is_array($newRaw)) {
            $oldArr = is_array($oldRaw) ? $oldRaw : [];
            $newArr = is_array($newRaw) ? $newRaw : [];

            if ($this->isPriceStructureArray($oldArr) || $this->isPriceStructureArray($newArr)) {
                $oldNormalized = $this->normalizePriceArray($oldArr);
                $newNormalized = $this->normalizePriceArray($newArr);
            } else {
                $oldNormalized = $this->normalizeGenericArray($oldArr);
                $newNormalized = $this->normalizeGenericArray($newArr);
            }

            return json_encode($oldNormalized, JSON_UNESCAPED_UNICODE) !== json_encode($newNormalized, JSON_UNESCAPED_UNICODE);
        }

        $oldIsEmpty = ($oldRaw === null || $oldRaw === '');
        $newIsEmpty = ($newRaw === null || $newRaw === '');

        if ($oldIsEmpty && $newIsEmpty) {
            return false;
        }

        $valueType = $fieldData['value_type'] ?? 'text';

        if (in_array($valueType, ['decimal', 'price', 'number', 'qty'], true)) {
            $oldNormalized = $oldRaw !== null ? $this->normalizeDecimalString((string)$oldRaw) : '';
            $newNormalized = $newRaw !== null ? $this->normalizeDecimalString((string)$newRaw) : '';

            if ($oldNormalized === $newNormalized) {
                return false;
            }
        }

        if ($oldBaseline === $newBaseline && $oldRaw === $newRaw) {
            return false;
        }

        switch ($valueType) {
            case 'boolean':
                $normalizeBoolean = static function ($value): string {
                    if ($value === null || $value === '') {
                        return self::NULL_SENTINEL;
                    }
                    if (in_array($value, [true, 1, '1'], true)) {
                        return '1';
                    }
                    if (in_array($value, [false, 0, '0'], true)) {
                        return '0';
                    }
                    return (string)$value;
                };

                if ($normalizeBoolean($oldRaw) === $normalizeBoolean($newRaw)) {
                    return false;
                }
                break;

            case 'select':
            case 'multiselect':
                if ($oldRaw === $newRaw) {
                    return false;
                }
                if ($oldRaw !== null && $newRaw !== null && (string)$oldRaw === (string)$newRaw) {
                    return false;
                }
                break;

            case 'date':
            case 'datetime':
                if ($oldIsEmpty || $newIsEmpty) {
                    return false;
                }

                $format = ($valueType === 'date') ? 'Y-m-d' : 'Y-m-d H:i:s';
                $oldDate = $this->parseToDateTime((string)$oldRaw, $format);
                $newDate = $this->parseToDateTime((string)$newRaw, $format);

                if ($oldDate !== null && $newDate !== null) {
                    if ($oldDate->format($format) === $newDate->format($format)) {
                        return false;
                    }
                }
                break;

            default:
                if ($oldRaw !== null && $newRaw !== null && (string)$oldRaw === (string)$newRaw) {
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Safely parse raw string to DateTimeImmutable object.
     *
     * @param string $dateStr
     * @param string $format
     * @return DateTimeImmutable|null
     */
    protected function parseToDateTime(string $dateStr, string $format): ?DateTimeImmutable
    {
        try {
            $dt = new DateTimeImmutable($dateStr);
            return $dt;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Transform value to display label.
     *
     * @param array $fieldData
     * @param mixed $value
     * @param string $fieldCode
     * @param string $tableName
     * @return string|null
     */
    protected function transformValueToDisplayLabel(array $fieldData, $value, string $fieldCode = '', string $tableName = ''): ?string
    {
        if ($value === null) {
            return '— (NULL)';
        }

        if (is_array($value)) {
            $normalized = $this->isPriceStructureArray($value)
                ? $this->normalizePriceArray($value)
                : $this->normalizeGenericArray($value);

            return json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        $strValue = $this->valueToString($value);
        if ($strValue === '') {
            return '— (empty)';
        }

        $helper = $this->_getHelper();
        $valueType = $fieldData['value_type'] ?? 'text';

        if (in_array($valueType, ['decimal', 'price', 'number', 'qty'], true) && is_numeric($strValue)) {
            return number_format((float)$strValue, 2, '.', '');
        }

        if (($valueType === 'date' || $valueType === 'datetime') || $this->matchesDateFormatPattern($strValue)) {
            $timestamp = strtotime($strValue);
            if ($timestamp !== false) {
                return $valueType === 'datetime' ? date('d.m.Y H:i:s', $timestamp) : date('d.m.Y', $timestamp);
            }
        }

        $options = $this->loadFieldOptionDictionary($fieldData, $fieldCode, $tableName);

        if (!empty($options)) {
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

        return $strValue;
    }

    /**
     * Load field option dictionary.
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
                if ($sourceClass === 'eav/entity_attribute_source_table') {
                    if (!empty($code)) {
                        $entityTypeCode = !empty($tableName) ? str_replace('_entity', '', $tableName) : 'customer';

                        /** @var Mage_Eav_Model_Entity_Attribute $attribute */
                        $attribute = Mage::getResourceModel('eav/entity_attribute_collection')
                            ->setCodeFilter($code)
                            ->setEntityTypeFilter(Mage::getModel('eav/entity_type')->loadByCode($entityTypeCode)->getId())
                            ->getFirstItem();

                        if ($attribute && $attribute->getId()) {
                            $optionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection');

                            if ($optionCollection) {
                                $optionCollection
                                    ->setAttributeFilter($attribute->getId())
                                    ->setStoreFilter(Mage::app()->getStore()->getId());

                                foreach ($optionCollection as $option) {
                                    $options[(string)$option->getOptionId()] = (string)$option->getValue();
                                }
                            }
                        }
                    }
                    return $options;
                }

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
     * Check if string matches date format pattern.
     *
     * @param string $val
     * @return bool
     */
    protected function matchesDateFormatPattern(string $val): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}/', $val) || preg_match('/^\d{2}[\.\/]\d{2}[\.\/]\d{4}/', $val);
    }

    /**
     * Resolve current user name.
     *
     * @return string
     */
    protected function resolveCurrentUserName(): string
    {
        $helper = Mage::helper('modestox_deltalog');

        switch ($helper->detectContext()) {
            case Modestox_DeltaLog_Helper_Data::CONTEXT_ADMIN:
                $adminUser = Mage::getSingleton('admin/session')->getUser();

                return $adminUser && $adminUser->getId()
                    ? $adminUser->getUsername() . ' (Admin)'
                    : 'Admin (unknown user)';

            case Modestox_DeltaLog_Helper_Data::CONTEXT_BACKGROUND:
                $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
                if ($script === 'cron.php') {
                    return PHP_SAPI === 'cli' ? 'Cron Process' : 'Cron Process (web)';
                }
                if (PHP_SAPI === 'cli') {
                    return sprintf('CLI (%s)', $script !== '' ? $script : 'script');
                }

                return 'Web API';
        }

        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('customer/session');
        if ($customerSession->isLoggedIn()) {
            return sprintf('Customer #%d [Customer]', (int)$customerSession->getCustomerId());
        }

        return 'Guest / Frontend System';
    }

    /**
     * Check if array represents Tier Price or Group Price structures.
     *
     * @param array $data
     * @return bool
     */
    protected function isPriceStructureArray(array $data): bool
    {
        $firstItem = reset($data);

        return is_array($firstItem) && (
            isset($firstItem['cust_group']) ||
            isset($firstItem['price']) ||
            isset($firstItem['price_qty']) ||
            isset($firstItem['all_groups'])
        );
    }

    /**
     * Normalize Tier Price / Group Price arrays to eliminate false diffs caused by non-significant field ordering or string formatting.
     *
     * @param array $data
     * @return array
     */
    protected function normalizePriceArray(array $data): array
    {
        $normalized = [];

        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (isset($row['delete']) && (string)$row['delete'] === '1') {
                continue;
            }

            $cleanRow = [];

            if (isset($row['website_id'])) {
                $cleanRow['website_id'] = (int)$row['website_id'];
            }

            if (isset($row['cust_group'])) {
                $cleanRow['cust_group'] = (int)$row['cust_group'];
            } elseif (isset($row['all_groups']) && (int)$row['all_groups'] === 1) {
                $cleanRow['cust_group'] = 0;
            }

            if (isset($row['price_qty'])) {
                $cleanRow['price_qty'] = (float)$row['price_qty'];
            }

            if (isset($row['price'])) {
                $cleanRow['price'] = (float)$row['price'];
            }

            if (isset($row['is_percent'])) {
                $cleanRow['is_percent'] = (int)(bool)$row['is_percent'];
            }

            ksort($cleanRow);
            $normalized[] = $cleanRow;
        }

        usort($normalized, static function ($a, $b) {
            $grpA = $a['cust_group'] ?? 0;
            $grpB = $b['cust_group'] ?? 0;
            if ($grpA !== $grpB) {
                return $grpA <=> $grpB;
            }
            $qtyA = $a['price_qty'] ?? 0;
            $qtyB = $b['price_qty'] ?? 0;
            return $qtyA <=> $qtyB;
        });

        return $normalized;
    }

    /**
     * Normalize generic arrays.
     *
     * @param array $data
     * @return array
     */
    protected function normalizeGenericArray(array $data): array
    {
        $clean = array_map(static function ($item) {
            return is_scalar($item) ? (string)$item : $item;
        }, $data);

        sort($clean);

        return $clean;
    }

    /**
     * Save delta entries.
     *
     * @param array $rowsToInsert
     * @return void
     * @throws Exception
     */
    protected function saveDeltaEntries(array $rowsToInsert): void
    {
        $resource = Mage::getSingleton('core/resource');
        $writeAdapter = $resource->getConnection('core_write');

        $writeAdapter->insertMultiple(
            $resource->getTableName('modestox_deltalog/entry'),
            $rowsToInsert,
        );
    }
}
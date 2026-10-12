<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Model_Compiler
{
    public const CACHE_KEY = 'modestox_deltalog_compiled_rules';

    /** @var array|null per-request memo: the observer asks for the rules on every model save */
    protected static ?array $_memo = null;

    /**
     * Get compiled active tracked entities rules from Cache or Build on the fly
     *
     * @return array
     */
    public function getCompiledRules(): array
    {
        if (self::$_memo !== null) {
            return self::$_memo;
        }

        $cache = Mage::app()->loadCache(self::CACHE_KEY);
        if ($cache !== false) {
            $unserialized = @unserialize($cache, ['allowed_classes' => false]);
            // an EMPTY array is a valid cached result ("no rules"): without this the rules
            // would be rebuilt from the database on every single model save
            if (is_array($unserialized)) {
                return self::$_memo = $unserialized;
            }
        }

        return $this->recompileRulesCache();
    }

    /**
     * Build rules from DB + static XML config and save to OpenMage cache
     *
     * @return array
     */
    public function recompileRulesCache(): array
    {
        $compiledRules = [];
        /** @var Modestox_DeltaLog_Helper_Data $helper */
        $helper = Mage::helper('modestox_deltalog');

        // 1. Load active rules from Database
        /** @var Modestox_DeltaLog_Model_Resource_Rule_Collection $ruleCollection */
        $ruleCollection = Mage::getModel('modestox_deltalog/rule')->getCollection();
        $ruleCollection->addFieldToFilter('is_active', 1);

        /** @var Modestox_DeltaLog_Model_Rule $rule */
        foreach ($ruleCollection as $rule) {
            $tableName = $helper->stripTablePrefix(trim((string)$rule->getTableName()));
            if (!$tableName) {
                continue;
            }

            $fieldsData = [];

            /** @var Modestox_DeltaLog_Model_Resource_Rule_Field_Collection $fieldCollection */
            $fieldCollection = Mage::getModel('modestox_deltalog/rule_field')->getCollection();
            $fieldCollection->addFieldToFilter('rule_id', (int)$rule->getId());
            $fieldCollection->addFieldToFilter('is_active', 1);

            /** @var Modestox_DeltaLog_Model_Rule_Field $field */
            foreach ($fieldCollection as $field) {
                $fieldCode = trim((string)$field->getFieldCode());
                if (!$fieldCode) {
                    continue;
                }

                $fieldsData[$fieldCode] = [
                    'label'          => (string)($field->getFieldLabel() ?: $fieldCode),
                    'value_type'     => (string)($field->getValueType() ?: 'text'),
                    'source_model'   => $field->getSourceModel() ? (string)$field->getSourceModel() : null,
                    'helper_method'  => $field->getHelperMethod() ? (string)$field->getHelperMethod() : null,
                    'custom_options' => method_exists($field, 'getParsedCustomOptions')
                        ? $field->getParsedCustomOptions()
                        : [],
                ];
            }

            // Записываем правило в скомпилированный массив
            $compiledRules[$tableName] = [
                'title'          => (string)$rule->getTitle(),
                'table_name'     => $tableName,
                'model_alias'    => $rule->getModelAlias() ? (string)$rule->getModelAlias() : null,
                'title_field'    => $rule->getTitleField() ? (string)$rule->getTitleField() : null,
                'edit_url_route' => $rule->getEditUrlRoute() ? (string)$rule->getEditUrlRoute() : null,
                'fields'         => $fieldsData,
            ];

            $helper->debug('compiled rule "' . $tableName . '", fields: ' . count($fieldsData));
        }
        // 2. Merge static rules from XML config (Fallback)
        $xmlTracked = Mage::getConfig()->getNode('global/tracked_entities');
        if ($xmlTracked) {
            foreach ($xmlTracked->children() as $entityNode) {
                $tableName = $helper->stripTablePrefix((string)$entityNode->table_name);
                if (!isset($compiledRules[$tableName]) && $entityNode->fields) {
                    $fieldsData = [];
                    foreach ($entityNode->fields->children() as $code => $fieldNode) {
                        $rawLabel = $fieldNode->label ? (string)$fieldNode->label : (string)$fieldNode;
                        $fieldsData[$code] = [
                            'label'          => $rawLabel,
                            'value_type'     => (string)($fieldNode['type'] ?? 'text'),
                            'source_model'   => $fieldNode->source_model ? (string)$fieldNode->source_model : null,
                            'helper_method'  => $fieldNode->helper_method ? (string)$fieldNode->helper_method : null,
                            'custom_options' => [],
                        ];
                    }

                    $compiledRules[$tableName] = [
                        'title'          => (string)($entityNode->label ?? $tableName),
                        'table_name'     => $tableName,
                        'model_alias'    => $entityNode->model_alias ? (string)$entityNode->model_alias : null,
                        'title_field'    => $entityNode->title_field ? (string)$entityNode->title_field : null,
                        'edit_url_route' => $entityNode->edit_url_route ? (string)$entityNode->edit_url_route : null,
                        'fields'         => $fieldsData,
                    ];
                }
            }
        }

        // Save to OpenMage cache without expiration time
        Mage::app()->saveCache(
            serialize($compiledRules),
            self::CACHE_KEY,
            [Mage_Core_Model_Config::CACHE_TAG],
            null,
        );

        return self::$_memo = $compiledRules;
    }
}

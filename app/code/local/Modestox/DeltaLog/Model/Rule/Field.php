<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Model_Rule_Field extends Mage_Core_Model_Abstract
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('modestox_deltalog/rule_field');
    }

    /**
     * Get custom options map from JSON
     *
     * @return array
     */
    public function getParsedCustomOptions(): array
    {
        $raw = $this->getData('custom_options');
        if (empty($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Trigger rule cache recompile on field save
     *
     * @return Modestox_DeltaLog_Model_Rule_Field
     */
//    protected function _afterSave(): self
//    {
//        parent::_afterSave();
//        Mage::getModel('modestox_deltalog/compiler')->recompileRulesCache();
//
//        return $this;
//    }

    /**
     * Trigger rule cache recompile on field delete
     *
     * @return Modestox_DeltaLog_Model_Rule_Field
     */
    protected function _afterDelete(): self
    {
        parent::_afterDelete();
        Mage::getModel('modestox_deltalog/compiler')->recompileRulesCache();

        return $this;
    }
}
<?php

/**
 * Modestox CMS - E-commerce Platform
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   AGPL-3.0-or-later
 * @link      https://github.com/Modestox/modestox
 */
class Modestox_DeltaLog_Model_Rule extends Mage_Core_Model_Abstract
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('modestox_deltalog/rule');
    }

    /**
     * Get associated active fields collection for this rule
     *
     * @return Modestox_DeltaLog_Model_Resource_Rule_Field_Collection
     */
    public function getActiveFieldsCollection(): Modestox_DeltaLog_Model_Resource_Rule_Field_Collection
    {
        /** @var Modestox_DeltaLog_Model_Resource_Rule_Field_Collection $collection */
        $collection = Mage::getModel('modestox_deltalog/rule_field')->getCollection();
        $collection
            ->addFieldToFilter('rule_id', (int)$this->getId())
            ->addFieldToFilter('is_active', 1);

        return $collection;
    }

    /**
     * Recompile rules cache after rule save
     *
     * @return parent::_afterSave()
     */
    protected function _afterSave()
    {
        parent::_afterSave();

        /** @var Modestox_DeltaLog_Model_Compiler $compiler */
        $compiler = Mage::getModel('modestox_deltalog/compiler');
        $compiler->recompileRulesCache();

        return $this;
    }

    /**
     * Recompile delta rules cache after model delete
     *
     * @return Modestox_DeltaLog_Model_Rule
     */
    protected function _afterDelete(): self
    {
        parent::_afterDelete();
        Mage::getModel('modestox_deltalog/compiler')->recompileRulesCache();

        return $this;
    }
}

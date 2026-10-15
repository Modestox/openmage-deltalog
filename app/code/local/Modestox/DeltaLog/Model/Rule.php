<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Model_Rule extends Mage_Core_Model_Abstract
{
    /**
     * Constructor.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('modestox_deltalog/rule');
    }

    /**
     * Get active fields collection.
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
     * Handle actions after save.
     *
     * @return self
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
     * Handle actions after delete.
     *
     * @return self
     */
    protected function _afterDelete(): self
    {
        parent::_afterDelete();
        Mage::getModel('modestox_deltalog/compiler')->recompileRulesCache();

        return $this;
    }
}
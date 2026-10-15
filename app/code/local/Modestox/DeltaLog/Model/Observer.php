<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Model_Observer
{
    /**
     * Execute on model save after.
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function executeOnModelSaveAfter(Varien_Event_Observer $observer): void
    {
        /** @var Modestox_DeltaLog_Helper_Data $helper */
        $helper = Mage::helper('modestox_deltalog');

        if (!$helper->isEnabled()) {
            return;
        }

        /** @var Mage_Core_Model_Abstract $model */
        $model = $observer->getEvent()->getObject();
        if (!$model instanceof Mage_Core_Model_Abstract || !$model->getId()) {
            return;
        }

        if (!$helper->isCurrentContextTracked()) {
            return;
        }

        try {
            /** @var Modestox_DeltaLog_Model_Processor $processor */
            $processor = Mage::getModel('modestox_deltalog/processor');
            $processor->processModelChanges($model);
        } catch (Throwable $e) {
            Mage::logException($e);
        }
    }
}
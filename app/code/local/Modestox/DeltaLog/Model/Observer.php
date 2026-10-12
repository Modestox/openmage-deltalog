<?php

/**
 * Modestox CMS - E-commerce Platform
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   AGPL-3.0-or-later
 * @link      https://github.com/Modestox/modestox
 */
class Modestox_DeltaLog_Model_Observer
{
    /**
     * Intercept model save event and delegate delta processing
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function executeOnModelSaveAfter(Varien_Event_Observer $observer): void
    {
        Mage::log('DeltaLog Triggered for Table: ээ', null, 'deltalog.log', true);
        // 1. Check if save happens within Admin Panel session
        if (!Mage::getSingleton('admin/session')->isLoggedIn()) {
            return;
        }

        // 2. Check system configuration toggle
        if (!Mage::getStoreConfigFlag('modestox_deltalog/general/enabled')) {
            return;
        }

        /** @var Mage_Core_Model_Abstract $model */
        $model = $observer->getEvent()->getObject();
        if (!$model || !$model->getId()) {
            return;
        }

        try {
            /** @var Modestox_DeltaLog_Model_Processor $processor */
            $processor = Mage::getModel('modestox_deltalog/processor');
            $processor->processModelChanges($model);
        } catch (Throwable $e) {
            // Log error safely without breaking standard save flow
            Mage::logException($e);
            Mage::log('DeltaLog Error: ' . $e->getMessage(), null, 'deltalog.log', true);
        }
    }
}

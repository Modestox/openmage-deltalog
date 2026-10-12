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
     * Intercept model save event and delegate delta processing
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function executeOnModelSaveAfter(Varien_Event_Observer $observer): void
    {
        /** @var Modestox_DeltaLog_Helper_Data $helper */
        $helper = Mage::helper('modestox_deltalog');

        // This observer runs on EVERY model save in the whole shop, so the cheapest checks come first.
        if (!$helper->isEnabled()) {
            return;
        }

        // Check the area BEFORE touching admin/session: instantiating that session outside the admin
        // area (frontend, cron, CLI scripts) starts a PHP session as a side effect.
        if (!Mage::app()->getStore()->isAdmin()) {
            return;
        }
        if (!Mage::getSingleton('admin/session')->isLoggedIn()) {
            return;
        }

        /** @var Mage_Core_Model_Abstract $model */
        $model = $observer->getEvent()->getObject();
        if (!$model instanceof Mage_Core_Model_Abstract || !$model->getId()) {
            return;
        }

        try {
            /** @var Modestox_DeltaLog_Model_Processor $processor */
            $processor = Mage::getModel('modestox_deltalog/processor');
            $processor->processModelChanges($model);
        } catch (Throwable $e) {
            // never break the standard save flow
            Mage::logException($e);
        }
    }
}

<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Adminhtml_HistoryController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init action layout and active menu
     *
     * @return Modestox_DeltaLog_Adminhtml_HistoryController
     */
    protected function _initAction(): self
    {
        $this->loadLayout()
            ->_setActiveMenu('system/modestox_deltalog')
            ->_addBreadcrumb(
                Mage::helper('modestox_deltalog')->__('Delta Log Entries'),
                Mage::helper('modestox_deltalog')->__('Delta Log Entries')
            );
        return $this;
    }

    /**
     * Index Action - Render main grid container
     *
     * @return void
     */
    public function indexAction(): void
    {
        $this->_initAction();
        $this->_title(Mage::helper('modestox_deltalog')->__('Delta Log Entries'));

        $block = $this->getLayout()->createBlock('modestox_deltalog/adminhtml_history');
        $this->_addContent($block);

        $this->renderLayout();
    }

    /**
     * Grid Action - Handles AJAX sorting, filtering, and pagination
     *
     * @return void
     */
    public function gridAction(): void
    {
        $this->loadLayout();
        $grid = $this->getLayout()->createBlock('modestox_deltalog/adminhtml_history_grid');
        $this->getResponse()->setBody($grid->toHtml());
    }

    /**
     * ACL check for admin authorization
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/modestox_deltalog/history');
    }
}
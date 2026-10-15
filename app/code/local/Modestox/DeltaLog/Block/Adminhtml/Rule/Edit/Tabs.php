<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Block_Adminhtml_Rule_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    /**
     * Helper instance.
     *
     * @var Modestox_DeltaLog_Helper_Data|null
     */
    protected ?Modestox_DeltaLog_Helper_Data $_helper = null;

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
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('deltalog_rule_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle($this->_getHelper()->__('Rule Information'));
    }

    /**
     * Prepare tabs layout.
     *
     * @return self
     */
    protected function _beforeToHtml(): self
    {
        $helper = $this->_getHelper();

        $this->addTab('general_section', [
            'label'   => $helper->__('General Configuration'),
            'title'   => $helper->__('General Configuration'),
            'content' => $this->getLayout()->createBlock('modestox_deltalog/adminhtml_rule_edit_tab_general')->toHtml(),
            'active'  => true,
        ]);

        $this->addTab('fields_section', [
            'label'   => $helper->__('Tracked Fields'),
            'title'   => $helper->__('Tracked Fields'),
            'content' => $this->getLayout()->createBlock('modestox_deltalog/adminhtml_rule_edit_tab_fields')->toHtml(),
        ]);

        $this->addTab('instruction_section', [
            'label'   => Mage::helper('modestox_deltalog')->__('Instruction'),
            'title'   => Mage::helper('modestox_deltalog')->__('Instruction & Usage Guide'),
            'content' => $this->getLayout()->createBlock('modestox_deltalog/adminhtml_rule_edit_tab_instruction')->toHtml(),
        ]);

        return parent::_beforeToHtml();
    }
}
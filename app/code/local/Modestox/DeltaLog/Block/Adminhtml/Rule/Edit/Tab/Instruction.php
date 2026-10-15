<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Block_Adminhtml_Rule_Edit_Tab_Instruction extends Mage_Adminhtml_Block_Template implements
    Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * Constructor.
     */
    protected function _construct(): void
    {
        parent::_construct();
        $this->setTemplate('modestox/deltalog/rule/instruction/full_guide.phtml');
    }

    /**
     * Prepare layout.
     *
     * @return parent::_prepareLayout()
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'instruction_general',
            $this->getLayout()->createBlock('adminhtml/template')->setTemplate('modestox/deltalog/rule/instruction/general.phtml'),
        );

        $this->setChild(
            'instruction_fields',
            $this->getLayout()->createBlock('adminhtml/template')->setTemplate('modestox/deltalog/rule/instruction/fields.phtml'),
        );

        return parent::_prepareLayout();
    }

    /**
     * Get tab label.
     *
     * @return string
     */
    public function getTabLabel(): string
    {
        return Mage::helper('modestox_deltalog')->__('Instruction');
    }

    /**
     * Get tab title.
     *
     * @return string
     */
    public function getTabTitle(): string
    {
        return Mage::helper('modestox_deltalog')->__('Instruction & Usage Guide');
    }

    /**
     * Can show tab.
     *
     * @return bool
     */
    public function canShowTab(): bool
    {
        return true;
    }

    /**
     * Is tab hidden.
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        return false;
    }
}
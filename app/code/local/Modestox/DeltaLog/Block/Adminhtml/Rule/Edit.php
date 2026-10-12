<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Block_Adminhtml_Rule_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * Helper instance
     *
     * @var Modestox_DeltaLog_Helper_Data|null
     */
    protected ?Modestox_DeltaLog_Helper_Data $_helper = null;

    /**
     * Get module helper
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
     * Initialize edit container
     */
    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'modestox_deltalog';
        $this->_controller = 'adminhtml_rule';

        parent::__construct();

        $this->_updateButton('save', 'label', $this->_getHelper()->__('Save Rule'));
        $this->_updateButton('delete', 'label', $this->_getHelper()->__('Delete Rule'));

        $this->_addButton('saveandcontinue', [
            'label'   => $this->_getHelper()->__('Save And Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class'   => 'save',
        ], -100);

        $this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    /**
     * Header text
     *
     * @return string
     */
    public function getHeaderText(): string
    {
        $model = Mage::registry('current_deltalog_rule');
        if ($model && $model->getId()) {
            return sprintf(
                "%s '%s'",
                $this->_getHelper()->__("Edit Rule"),
                $this->escapeHtml($model->getTitle()),
            );
        }

        return $this->_getHelper()->__('New Tracking Rule');
    }
}
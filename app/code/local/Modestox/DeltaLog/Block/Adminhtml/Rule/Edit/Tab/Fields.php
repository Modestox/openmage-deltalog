<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Block_Adminhtml_Rule_Edit_Tab_Fields extends Mage_Adminhtml_Block_Widget
{
    /**
     * Set custom template for fields grid
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('modestox/deltalog/rule/fields.phtml');
    }

    /**
     * Get rule fields collection
     *
     * @return Modestox_DeltaLog_Model_Resource_Rule_Field_Collection|array
     */
    public function getRuleFields()
    {
        $model = Mage::registry('current_deltalog_rule');
        if ($model && $model->getId()) {
            return $model->getActiveFieldsCollection();
        }

        return [];
    }
}
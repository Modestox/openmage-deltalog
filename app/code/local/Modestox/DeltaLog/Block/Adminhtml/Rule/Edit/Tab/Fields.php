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
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('modestox/deltalog/rule/fields.phtml');
    }

    /**
     * Get rule fields.
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

    /**
     * Format custom options to raw string.
     *
     * @param mixed $customOptions
     * @return string
     */
    public function formatCustomOptionsToRaw($customOptions): string
    {
        if (empty($customOptions)) {
            return '';
        }

        if (is_string($customOptions)) {
            $decoded = json_decode($customOptions, true);
            $customOptions = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($customOptions)) {
            return '';
        }

        $pairs = [];
        foreach ($customOptions as $key => $val) {
            $cleanKey = str_replace('v_', '', (string)$key);
            $pairs[] = sprintf('%s:%s', $cleanKey, $val);
        }

        return implode(', ', $pairs);
    }
}
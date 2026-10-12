<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Block_Adminhtml_Rule_Edit_Tab_General extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Prepare general form fields
     *
     * @return self
     */
    protected function _prepareForm(): self
    {
        $model = Mage::registry('current_deltalog_rule');
        $helper = Mage::helper('modestox_deltalog');

        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('base_fieldset', [
            'legend' => $helper->__('General Settings'),
        ]);

        if ($model && $model->getId()) {
            $fieldset->addField('rule_id', 'hidden', [
                'name' => 'rule_id',
            ]);
        }

        $fieldset->addField('title', 'text', [
            'name'     => 'title',
            'label'    => $helper->__('Rule Title'),
            'required' => true,
        ]);

        $fieldset->addField('is_active', 'select', [
            'name'   => 'is_active',
            'label'  => $helper->__('Status'),
            'values' => [
                1 => $helper->__('Enabled'),
                0 => $helper->__('Disabled'),
            ],
        ]);

        $fieldset->addField('entity_type', 'select', [
            'name'   => 'entity_type',
            'label'  => $helper->__('Entity Type'),
            'values' => [
                'flat' => $helper->__('Flat Table'),
                'eav'  => $helper->__('EAV Model'),
            ],
        ]);

        $fieldset->addField('table_name', 'text', [
            'name'     => 'table_name',
            'label'    => $helper->__('Database Table / Entity Code'),
            'required' => true,
        ]);

        $fieldset->addField('model_alias', 'text', [
            'name'  => 'model_alias',
            'label' => $helper->__('Model Alias'),
        ]);

        $fieldset->addField('title_field', 'text', [
            'name'  => 'title_field',
            'label' => $helper->__('Title Column Field'),
        ]);

        $fieldset->addField('edit_url_route', 'text', [
            'name'  => 'edit_url_route',
            'label' => $helper->__('Admin Edit URL Route'),
        ]);

        if ($model && $model->getData()) {
            $form->setValues($model->getData());
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
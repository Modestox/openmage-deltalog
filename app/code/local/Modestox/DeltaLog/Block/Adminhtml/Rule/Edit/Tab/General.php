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
            'note'     => $helper->__('Friendly name for this tracking rule in the admin grid.'),
        ]);

        $fieldset->addField('is_active', 'select', [
            'name'   => 'is_active',
            'label'  => $helper->__('Status'),
            'values' => [
                1 => $helper->__('Enabled'),
                0 => $helper->__('Disabled'),
            ],
            'note'   => $helper->__('Enable or disable change tracking for this rule.'),
        ]);

        // Automatically fetch all EAV entity types from DB
        /** @var Mage_Eav_Model_Resource_Entity_Type_Collection $eavTypes */
        $eavTypes = Mage::getResourceModel('eav/entity_type_collection');
        $eavMappingJs = [];

        $entityTypeOptions = [
            'flat' => $helper->__('Flat Table (Custom)'),
        ];

        foreach ($eavTypes as $eavType) {
            $code = (string)$eavType->getEntityTypeCode();
            $modelAlias = (string)$eavType->getEntityModel();

            $tableName = '';
            if ($modelAlias) {
                try {
                    $dummyModel = Mage::getModel($modelAlias);
                    if ($dummyModel && $dummyModel->getResource()) {
                        $resource = $dummyModel->getResource();
                        if (method_exists($resource, 'getMainTable')) {
                            $tableName = $helper->stripTablePrefix($resource->getMainTable());
                        } elseif (method_exists($resource, 'getEntityTable')) {
                            $tableName = $helper->stripTablePrefix($resource->getEntityTable());
                        }
                    }
                } catch (Throwable $e) {
                    // Fallback
                }
            }

            if (empty($tableName)) {
                $rawTable = (string)$eavType->getEntityTable();
                $tableName = $rawTable ? $helper->stripTablePrefix($rawTable) : $code . '_entity';
            }

            if (empty($modelAlias)) {
                $modelAlias = $code . '/' . $code;
            }

            $entityTypeOptions['eav_' . $code] = sprintf(
                '%s (EAV: %s)',
                ucfirst(str_replace('_', ' ', $code)),
                $code,
            );

            $eavMappingJs['eav_' . $code] = [
                'table_name'  => $tableName,
                'model_alias' => $modelAlias,
            ];
        }

        $jsMappingJson = json_encode($eavMappingJs);

        $jsScript = "
        <script type=\"text/javascript\">
            var eavMappingData = {$jsMappingJson};

            function updateEntityTypeFields(selectedVal) {
                var tableNameInput = $('table_name');
                var modelAliasInput = $('model_alias');
                var realEntityTypeInput = $('entity_type');

                if (!tableNameInput || !modelAliasInput || !realEntityTypeInput) {
                    return;
                }

                if (selectedVal === 'flat' || !selectedVal) {
                    realEntityTypeInput.value = 'flat';
                    
                    tableNameInput.readOnly = false;
                    modelAliasInput.readOnly = false;
                    
                    tableNameInput.removeClassName('disabled');
                    modelAliasInput.removeClassName('disabled');
                    tableNameInput.style.backgroundColor = '';
                    modelAliasInput.style.backgroundColor = '';
                } else if (eavMappingData[selectedVal]) {
                    var data = eavMappingData[selectedVal];

                    realEntityTypeInput.value = 'eav';
                    tableNameInput.value = data.table_name;
                    modelAliasInput.value = data.model_alias;

                    tableNameInput.readOnly = true;
                    modelAliasInput.readOnly = true;

                    tableNameInput.addClassName('disabled');
                    modelAliasInput.addClassName('disabled');
                    tableNameInput.style.backgroundColor = '#f0f0f0';
                    modelAliasInput.style.backgroundColor = '#f0f0f0';
                }
            }

            function bindEntityTypeEvents() {
                var selectEl = $('entity_type_select');
                if (selectEl) {
                    Event.stopObserving(selectEl, 'change');
                    Event.observe(selectEl, 'change', function() {
                        updateEntityTypeFields(this.value);
                    });
                    updateEntityTypeFields(selectEl.value);
                    return true;
                }
                return false;
            }

            document.observe('dom:loaded', function() {
                if (!bindEntityTypeEvents()) {
                    setTimeout(bindEntityTypeEvents, 300);
                }
            });
        </script>
        ";

        $fieldset->addField('entity_type_select', 'select', [
            'name'               => 'entity_type_select',
            'label'              => $helper->__('Entity / Data Source Type'),
            'values'             => $entityTypeOptions,
            'note'               => $helper->__('Select an existing EAV Entity or choose "Flat Table (Custom)" for standard DB tables.'),
            'after_element_html' => $jsScript,
        ]);

        $fieldset->addField('entity_type', 'hidden', [
            'name' => 'entity_type',
        ]);

        $fieldset->addField('table_name', 'text', [
            'name'     => 'table_name',
            'label'    => $helper->__('Database Table / Entity Code'),
            'required' => true,
            'note'     => $helper->__('Main database table name without prefix or EAV entity type code.'),
        ]);

        $fieldset->addField('model_alias', 'text', [
            'name'  => 'model_alias',
            'label' => $helper->__('Model Alias'),
            'note'  => $helper->__('OpenMage model URI alias (e.g. customer/customer or catalog/product).'),
        ]);

        $fieldset->addField('title_field', 'text', [
            'name'  => 'title_field',
            'label' => $helper->__('Title Column Field'),
            'note'  => $helper->__('Column or attribute code used as a human-readable title in log entries (e.g. email, name, sku).'),
        ]);

        $fieldset->addField('edit_url_route', 'text', [
            'name'  => 'edit_url_route',
            'label' => $helper->__('Admin Edit URL Route'),
            'note'  => $helper->__('Admin route for direct link creation to the entity edit page (e.g. adminhtml/customer/edit).'),
        ]);

        // Полная инструкция на русском языке под формой
        $instructionBlock = $this
            ->getLayout()
            ->createBlock('adminhtml/template')
            ->setTemplate('modestox/deltalog/rule/instruction/general.phtml');

        $fieldset->addField('configuration_guide', 'note', [
            'text' => '</td></tr></table>' . $instructionBlock->toHtml() . '<table class="form-list"><tr><td style="display:none;">',
        ]);

        $formData = $model ? $model->getData() : [];
        if (!empty($formData)) {
            $currentEntityType = $formData['entity_type'] ?? 'flat';
            $currentTableName = $formData['table_name'] ?? '';

            if ($currentEntityType === 'eav' && !empty($currentTableName)) {
                $formData['entity_type_select'] = 'eav_' . str_replace('_entity', '', $currentTableName);
            } else {
                $formData['entity_type_select'] = 'flat';
            }

            $form->setValues($formData);
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
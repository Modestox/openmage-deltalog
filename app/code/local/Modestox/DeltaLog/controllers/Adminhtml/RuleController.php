<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Adminhtml_RuleController extends Mage_Adminhtml_Controller_Action
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
     * Check ACL permissions.
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/modestox_deltalog/rule');
    }

    /**
     * Init action layout and breadcrumbs.
     *
     * @return self
     */
    protected function _initAction(): self
    {
        $this
            ->loadLayout()
            ->_setActiveMenu('system/modestox_deltalog')
            ->_addBreadcrumb($this->_getHelper()->__('System'), $this->_getHelper()->__('System'))
            ->_addBreadcrumb($this->_getHelper()->__('Delta Log Rules'), $this->_getHelper()->__('Delta Log Rules'));

        return $this;
    }

    /**
     * Index action.
     *
     * @return void
     */
    public function indexAction(): void
    {
        $this->_initAction();
        $this->_title($this->_getHelper()->__('Delta Log Tracking Rules'));
        $this->renderLayout();
    }

    /**
     * Grid action.
     *
     * @return void
     */
    public function gridAction(): void
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * New action.
     *
     * @return void
     */
    public function newAction(): void
    {
        $this->_forward('edit');
    }

    /**
     * Edit action.
     *
     * @return void
     */
    public function editAction(): void
    {
        $id = (int)$this->getRequest()->getParam('id');
        /** @var Modestox_DeltaLog_Model_Rule $model */
        $model = Mage::getModel('modestox_deltalog/rule');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError($this->_getHelper()->__('This rule no longer exists.'));
                $this->_redirect('*/*/index');
                return;
            }
        }

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        Mage::register('current_deltalog_rule', $model);

        $this->_initAction();
        $this->_title($id ? $model->getTitle() : $this->_getHelper()->__('New Rule'));
        $this->renderLayout();
    }

    /**
     * Save action.
     *
     * @return void
     */
    public function saveAction(): void
    {
        $data = $this->getRequest()->getPost();
        if (!$data) {
            $this->_redirect('*/*/index');
            return;
        }

        $id = (int)$this->getRequest()->getParam('rule_id');
        /** @var Modestox_DeltaLog_Model_Rule $model */
        $model = Mage::getModel('modestox_deltalog/rule');

        if ($id) {
            $model->load($id);
        }

        $model->addData([
            'title'          => trim((string)($data['title'] ?? '')),
            'entity_type'    => trim((string)($data['entity_type'] ?? 'flat')),
            'table_name'     => trim((string)($data['table_name'] ?? '')),
            'model_alias'    => trim((string)($data['model_alias'] ?? '')) ?: null,
            'title_field'    => trim((string)($data['title_field'] ?? '')) ?: null,
            'edit_url_route' => trim((string)($data['edit_url_route'] ?? '')) ?: null,
            'is_active'      => isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ]);

        try {
            $model->save();
            $ruleId = (int)$model->getId();

            if (isset($data['fields']) && is_array($data['fields'])) {
                $existingFields = [];
                foreach ($model->getActiveFieldsCollection() as $field) {
                    $existingFields[$field->getId()] = $field;
                }

                $processedIds = [];

                foreach ($data['fields'] as $fieldKey => $fieldData) {
                    $fieldCode = trim((string)($fieldData['field_code'] ?? ''));
                    $fieldLabel = trim((string)($fieldData['field_label'] ?? ''));

                    if ($fieldCode === '' || $fieldLabel === '') {
                        continue;
                    }

                    /** @var Modestox_DeltaLog_Model_Rule_Field $fieldModel */
                    $fieldModel = Mage::getModel('modestox_deltalog/rule_field');

                    if (is_numeric($fieldKey) && isset($existingFields[$fieldKey])) {
                        $fieldModel = $existingFields[$fieldKey];
                    }

                    $fieldModel->addData([
                        'rule_id'            => $ruleId,
                        'field_code'         => $fieldCode,
                        'field_label'        => $fieldLabel,
                        'value_type'         => trim((string)($fieldData['value_type'] ?? 'text')),
                        'source_model'       => trim((string)($fieldData['source_model'] ?? '')) ?: null,
                        'helper_method'      => trim((string)($fieldData['helper_method'] ?? '')) ?: null,
                        'custom_options_raw' => trim((string)($fieldData['custom_options_raw'] ?? '')),
                        'is_active'          => isset($fieldData['is_active']) ? (int)$fieldData['is_active'] : 1,
                    ]);

                    $fieldModel->save();
                    if ($fieldModel->getId()) {
                        $processedIds[] = (int)$fieldModel->getId();
                    }
                }

                foreach ($existingFields as $existingId => $fieldObj) {
                    if (!in_array($existingId, $processedIds, true)) {
                        $fieldObj->delete();
                    }
                }
            }

            Mage::getModel('modestox_deltalog/compiler')->recompileRulesCache();

            Mage::getSingleton('adminhtml/session')->addSuccess($this->_getHelper()->__('Tracking rule has been successfully saved.'));
            Mage::getSingleton('adminhtml/session')->setFormData(false);

            if ($this->getRequest()->getParam('back')) {
                $this->_redirect('*/*/edit', ['id' => $model->getId()]);
                return;
            }

            $this->_redirect('*/*/index');
            return;
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::getSingleton('adminhtml/session')->setFormData($data);
            $this->_redirect('*/*/edit', ['id' => $id]);
            return;
        }
    }

    /**
     * Delete action.
     *
     * @return void
     */
    public function deleteAction(): void
    {
        $id = (int)$this->getRequest()->getParam('id');
        if ($id) {
            try {
                /** @var Modestox_DeltaLog_Model_Rule $model */
                $model = Mage::getModel('modestox_deltalog/rule');
                $model->load($id);
                if ($model->getId()) {
                    $model->delete();
                    Mage::getSingleton('adminhtml/session')->addSuccess($this->_getHelper()->__('Tracking rule has been deleted.'));
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    /**
     * Mass delete action.
     *
     * @return void
     */
    public function massDeleteAction(): void
    {
        $ruleIds = $this->getRequest()->getParam('rule_ids');
        if (!is_array($ruleIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->_getHelper()->__('Please select rule(s) to delete.'));
        } else {
            try {
                $count = 0;
                foreach ($ruleIds as $ruleId) {
                    /** @var Modestox_DeltaLog_Model_Rule $model */
                    $model = Mage::getModel('modestox_deltalog/rule')->load((int)$ruleId);
                    if ($model->getId()) {
                        $model->delete();
                        $count++;
                    }
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->_getHelper()->__('Total of %d rule(s) were successfully deleted.', $count),
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    /**
     * Mass status action.
     *
     * @return void
     */
    public function massStatusAction(): void
    {
        $ruleIds = $this->getRequest()->getParam('rule_ids');
        $status = (int)$this->getRequest()->getParam('status');

        if (!is_array($ruleIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->_getHelper()->__('Please select rule(s).'));
        } else {
            try {
                $count = 0;
                foreach ($ruleIds as $ruleId) {
                    /** @var Modestox_DeltaLog_Model_Rule $model */
                    $model = Mage::getModel('modestox_deltalog/rule')->load((int)$ruleId);
                    if ($model->getId()) {
                        $model->setIsActive($status)->save();
                        $count++;
                    }
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->_getHelper()->__('Total of %d rule(s) status were successfully updated.', $count),
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    /**
     * Load fields action.
     *
     * @return void
     */
    public function loadFieldsAction(): void
    {
        $tableName = trim((string)$this->getRequest()->getParam('table_name'));
        $entityType = trim((string)$this->getRequest()->getParam('entity_type', 'flat'));

        /** @var Modestox_DeltaLog_Model_Inspector $inspector */
        $inspector = Mage::getModel('modestox_deltalog/inspector');

        if ($entityType === 'eav') {
            $fields = $inspector->getEavAttributes($tableName);
        } else {
            $fields = $inspector->getTableColumns($tableName);
        }

        $this
            ->getResponse()
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode(array_values($fields)));
    }
}
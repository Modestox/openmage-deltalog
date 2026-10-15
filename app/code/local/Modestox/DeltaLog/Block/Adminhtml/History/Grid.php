<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Block_Adminhtml_History_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
     * Set grid default parameters
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('modestox_deltalog_grid');
        $this->setDefaultSort('log_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * Prepare grid collection
     *
     * @return Modestox_DeltaLog_Block_Adminhtml_History_Grid
     */
    protected function _prepareCollection(): self
    {
        /** @var Modestox_DeltaLog_Model_Resource_Entry_Collection $collection */
        $collection = Mage::getModel('modestox_deltalog/entry')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare grid columns
     *
     * @return Modestox_DeltaLog_Block_Adminhtml_History_Grid
     */
    protected function _prepareColumns(): self
    {
        $helper = $this->_getHelper();

        $this->addColumn('log_id', [
            'header' => $helper->__('ID'),
            'align'  => 'right',
            'width'  => '50px',
            'index'  => 'log_id',
            'type'   => 'number',
        ]);

        $this->addColumn('user_name', [
            'header' => $helper->__('User Name'),
            'align'  => 'left',
            'index'  => 'user_name',
            'width'  => '150px',
        ]);

        $this->addColumn('ip_address', [
            'header' => $helper->__('IP Address'),
            'align'  => 'left',
            'index'  => 'ip_address',
            'width'  => '120px',
        ]);

        $this->addColumn('entity_info', [
            'header'         => $helper->__('Entity Info'),
            'align'          => 'left',
            'index'          => 'entity_info',
            'width'          => '200px',
            'frame_callback' => [$this, 'decorateEntityInfoColumn'],
        ]);

        $this->addColumn('table_name', [
            'header'  => $helper->__('Table Name'),
            'align'   => 'left',
            'index'   => 'table_name',
            'type'    => 'options',
            'options' => $this->_getTableOptions(),
            'width'   => '180px',
        ]);

        // $this->addColumn('field_code', [
        //     'header'  => $helper->__('Field Name'),
        //     'align'   => 'left',
        //     'index'   => 'field_code',
        //     'type'    => 'options',
        //     'options' => $this->_getFieldOptions(),
        //     'width'   => '180px',
        // ]);

        $this->addColumn('field_code', [
            'header' => Mage::helper('modestox_deltalog')->__('Field Name'),
            'index'  => 'field_label',
            'type'   => 'text',
        ]);

        $this->addColumn('old_value', [
            'header' => $helper->__('From'),
            'align'  => 'left',
            'index'  => 'old_value',
        ]);

        $this->addColumn('new_value', [
            'header' => $helper->__('To'),
            'align'  => 'left',
            'index'  => 'new_value',
        ]);

        $this->addColumn('created_at', [
            'header' => $helper->__('Change Date'),
            'align'  => 'left',
            'index'  => 'created_at',
            'type'   => 'datetime',
            'width'  => '160px',
        ]);

        return parent::_prepareColumns();
    }

    /**
     * Render entity_info column as a live clickable link with active admin session key
     *
     * @param string $value
     * @param Mage_Core_Model_Abstract $row
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @param bool $isExport
     * @return string
     */
    public function decorateEntityInfoColumn(
        string $value,
        Mage_Core_Model_Abstract $row,
        Mage_Adminhtml_Block_Widget_Grid_Column $column,
        bool $isExport,
    ): string {
        $entityId = $row->getData('entity_id');
        $displayTitle = $value !== '' ? $value : ('ID: ' . $entityId);

        if ($isExport) {
            return $displayTitle;
        }

        $tableName = $row->getData('table_name');

        if (!$tableName || !$entityId) {
            return $this->escapeHtml($displayTitle);
        }

        $editRoute = null;

        // 1. Check rules compiled from DB
        /** @var Modestox_DeltaLog_Model_Compiler $compiler */
        $compiler = Mage::getModel('modestox_deltalog/compiler');
        $rules = $compiler->getCompiledRules();

        if (isset($rules[$tableName]['edit_url_route'])) {
            $editRoute = $rules[$tableName]['edit_url_route'];
        }

        // 2. Fallback to XML config
        if (!$editRoute) {
            $trackedEntities = Mage::getConfig()->getNode('global/tracked_entities');
            if ($trackedEntities) {
                foreach ($trackedEntities->children() as $entityConfig) {
                    if ((string)$entityConfig->table_name === $tableName && $entityConfig->edit_url_route) {
                        $editRoute = (string)$entityConfig->edit_url_route;
                        break;
                    }
                }
            }
        }

        if ($editRoute) {
            $liveUrl = Mage::helper('adminhtml')->getUrl($editRoute, ['id' => $entityId]);

            return sprintf(
                '<a href="%s" target="_blank" title="%s">%s</a>',
                $this->escapeHtml($liveUrl),
                $this->escapeHtml($this->_getHelper()->__('Edit Record')),
                $this->escapeHtml($displayTitle)
            );
        }

        return $this->escapeHtml($displayTitle);
    }

    /**
     * Get AJAX Grid URL
     *
     * @return string
     */
    public function getGridUrl(): string
    {
        return $this->getUrl('*/*/grid', ['_current' => true]);
    }

    /**
     * Build options list for table_name dropdown filter
     *
     * @return array
     */
    protected function _getTableOptions(): array
    {
        /** @var Modestox_DeltaLog_Model_Compiler $compiler */
        $compiler = Mage::getModel('modestox_deltalog/compiler');
        $rules = $compiler->getCompiledRules();

        $options = [];
        foreach ($rules as $tableName => $ruleData) {
            $options[$tableName] = sprintf('%s (%s)', $ruleData['title'] ?? $tableName, $tableName);
        }

        // Ensure current saved records table names are always visible in options
        $options['customer_entity'] = $options['customer_entity'] ?? 'Customer Entity (customer_entity)';

        return $options;
    }
}

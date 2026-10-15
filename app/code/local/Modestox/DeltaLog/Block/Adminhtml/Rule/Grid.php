<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Block_Adminhtml_Rule_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
        $this->setId('modestox_deltalog_rule_grid');
        $this->setDefaultSort('rule_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * Prepare collection.
     *
     * @return self
     */
    protected function _prepareCollection(): self
    {
        /** @var Modestox_DeltaLog_Model_Resource_Rule_Collection $collection */
        $collection = Mage::getModel('modestox_deltalog/rule')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare grid columns.
     *
     * @return self
     */
    protected function _prepareColumns(): self
    {
        $helper = $this->_getHelper();

        $this->addColumn('rule_id', [
            'header' => $helper->__('ID'),
            'align'  => 'right',
            'width'  => '50px',
            'index'  => 'rule_id',
            'type'   => 'number',
        ]);

        $this->addColumn('title', [
            'header' => $helper->__('Rule Title'),
            'align'  => 'left',
            'index'  => 'title',
        ]);

        $this->addColumn('entity_type', [
            'header'  => $helper->__('Entity Type'),
            'align'   => 'left',
            'index'   => 'entity_type',
            'type'    => 'options',
            'options' => [
                'flat' => $helper->__('Flat Table'),
                'eav'  => $helper->__('EAV Model'),
            ],
            'width'   => '120px',
        ]);

        $this->addColumn('table_name', [
            'header' => $helper->__('Table Name'),
            'align'  => 'left',
            'index'  => 'table_name',
            'width'  => '180px',
        ]);

        $this->addColumn('model_alias', [
            'header' => $helper->__('Model Alias'),
            'align'  => 'left',
            'index'  => 'model_alias',
            'width'  => '180px',
        ]);

        $this->addColumn('is_active', [
            'header'  => $helper->__('Status'),
            'align'   => 'center',
            'index'   => 'is_active',
            'type'    => 'options',
            'options' => [
                1 => $helper->__('Enabled'),
                0 => $helper->__('Disabled'),
            ],
            'width'   => '100px',
        ]);

        $this->addColumn('action', [
            'header'    => $helper->__('Action'),
            'width'     => '80px',
            'type'      => 'action',
            'getter'    => 'getId',
            'actions'   => [
                [
                    'caption' => $helper->__('Edit'),
                    'url'     => ['base' => '*/*/edit'],
                    'field'   => 'id',
                ],
            ],
            'filter'    => false,
            'sortable'  => false,
            'is_system' => true,
        ]);

        return parent::_prepareColumns();
    }

    /**
     * Prepare massaction.
     *
     * @return self
     */
    protected function _prepareMassaction(): self
    {
        $this->setMassactionIdField('rule_id');
        $this->getMassactionBlock()->setFormFieldName('rule_ids');

        $this->getMassactionBlock()->addItem('delete', [
            'label'   => $this->_getHelper()->__('Delete'),
            'url'     => $this->getUrl('*/*/massDelete'),
            'confirm' => $this->_getHelper()->__('Are you sure you want to delete selected rules?'),
        ]);

        $this->getMassactionBlock()->addItem('status', [
            'label'      => $this->_getHelper()->__('Change Status'),
            'url'        => $this->getUrl('*/*/massStatus'),
            'additional' => [
                'visibility' => [
                    'name'   => 'status',
                    'type'   => 'select',
                    'class'  => 'required-entry',
                    'label'  => $this->_getHelper()->__('Status'),
                    'values' => [
                        1 => $this->_getHelper()->__('Enabled'),
                        0 => $this->_getHelper()->__('Disabled'),
                    ],
                ],
            ],
        ]);

        return $this;
    }

    /**
     * Get row URL.
     *
     * @param Mage_Core_Model_Abstract $row
     * @return string
     */
    public function getRowUrl($row): string
    {
        return $this->getUrl('*/*/edit', ['id' => $row->getId()]);
    }

    /**
     * Get grid URL.
     *
     * @return string
     */
    public function getGridUrl(): string
    {
        return $this->getUrl('*/*/grid', ['_current' => true]);
    }
}
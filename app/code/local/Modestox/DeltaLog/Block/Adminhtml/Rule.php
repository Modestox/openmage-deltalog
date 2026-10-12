<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Block_Adminhtml_Rule extends Mage_Adminhtml_Block_Widget_Grid_Container
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
     * Initialize container parameters
     */
    public function __construct()
    {
        $this->_controller = 'adminhtml_rule';
        $this->_blockGroup = 'modestox_deltalog';
        $this->_headerText = $this->_getHelper()->__('Delta Log Rule Constructor');
        $this->_addButtonLabel = $this->_getHelper()->__('Add New Rule');

        parent::__construct();
    }
}

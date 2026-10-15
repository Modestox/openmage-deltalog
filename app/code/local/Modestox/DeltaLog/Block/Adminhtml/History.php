<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Block_Adminhtml_History extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_controller = 'adminhtml_history';
        $this->_blockGroup = 'modestox_deltalog';
        $this->_headerText = Mage::helper('modestox_deltalog')->__('Delta Log History');

        parent::__construct();
        $this->_removeButton('add');
    }
}
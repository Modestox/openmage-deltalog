<?php
/**
 * Modestox CMS - E-commerce Platform
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   AGPL-3.0-or-later
 * @link      https://github.com/Modestox/modestox
 */

class Modestox_DeltaLog_Block_Adminhtml_History extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_history';
        $this->_blockGroup = 'modestox_deltalog';
        $this->_headerText = Mage::helper('modestox_deltalog')->__('Delta Log History');

        parent::__construct();
        $this->_removeButton('add');
    }
}
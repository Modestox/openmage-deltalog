<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Model_Resource_Entry extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Initialize table and primary key
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('modestox_deltalog/entry', 'log_id');
    }
}
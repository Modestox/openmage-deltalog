<?php
/**
 * Modestox CMS - E-commerce Platform
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   AGPL-3.0-or-later
 * @link      https://github.com/Modestox/modestox
 */

class Modestox_DeltaLog_Model_Resource_Rule extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Initialize table and primary key
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('modestox_deltalog/rule', 'rule_id');
    }
}
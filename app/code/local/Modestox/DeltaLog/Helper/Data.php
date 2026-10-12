<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Helper_Data extends Mage_Core_Helper_Abstract
{
    public const XML_PATH_ENABLED = 'modestox_deltalog/general/enabled';
    public const XML_PATH_DEBUG = 'modestox_deltalog/general/debug';
    public const LOG_FILE = 'deltalog.log';

    public function isEnabled(): bool
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_ENABLED);
    }

    public function isDebug(): bool
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_DEBUG);
    }

    /**
     * Write to var/log/deltalog.log, but only when "Debug logging" is switched on.
     */
    public function debug(string $message): void
    {
        if ($this->isDebug()) {
            Mage::log('DeltaLog: ' . $message, Zend_Log::DEBUG, self::LOG_FILE, true);
        }
    }

    /**
     * Rules are stored and matched WITHOUT the DB table prefix, because that is what people type
     * into the rule form, while OpenMage resources return the prefixed name.
     */
    public function stripTablePrefix(string $table): string
    {
        $prefix = (string)Mage::getConfig()->getTablePrefix();

        return ($prefix !== '' && str_starts_with($table, $prefix))
            ? substr($table, strlen($prefix))
            : $table;
    }
}
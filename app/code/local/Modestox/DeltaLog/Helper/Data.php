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

    public const CONTEXT_ADMIN = 'admin';
    public const CONTEXT_BACKGROUND = 'background';
    public const CONTEXT_FRONTEND = 'frontend';
    public const XML_PATH_TRACKING_MODE = 'modestox_deltalog/general/tracking_mode';
    public const XML_PATH_RETENTION_DAYS = 'modestox_deltalog/general/retention_days';

    /**
     * Check if module is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_ENABLED);
    }

    /**
     * Get tracking mode.
     *
     * @return string
     */
    public function getTrackingMode(): string
    {
        $source = Modestox_DeltaLog_Model_Adminhtml_System_Config_Source_TrackingMode::class;
        $mode = (string)Mage::getStoreConfig(self::XML_PATH_TRACKING_MODE);

        return in_array($mode, [$source::MODE_ADMIN_ONLY, $source::MODE_ADMIN_BG, $source::MODE_EVERYWHERE], true)
            ? $mode
            : $source::MODE_ADMIN_ONLY;
    }

    /**
     * Check if debug logging is enabled.
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_DEBUG);
    }

    /**
     * Debug log.
     *
     * @param string $message
     * @return void
     */
    public function debug(string $message): void
    {
        if ($this->isDebug()) {
            Mage::log('DeltaLog: ' . $message, Zend_Log::DEBUG, self::LOG_FILE, true);
        }
    }

    /**
     * Strip table prefix.
     *
     * @param string $table
     * @return string
     */
    public function stripTablePrefix(string $table): string
    {
        $prefix = (string)Mage::getConfig()->getTablePrefix();

        return ($prefix !== '' && str_starts_with($table, $prefix))
            ? substr($table, strlen($prefix))
            : $table;
    }

    /**
     * Format source value.
     *
     * @param mixed $value
     * @param string $fieldCode
     * @param string $sourceModelClass
     * @param string $entityTypeCode
     * @return string
     */
    public function formatSourceValue($value, string $fieldCode, string $sourceModelClass = '', string $entityTypeCode = 'customer'): string
    {
        if ($value === '' || is_null($value)) {
            return '';
        }

        if (empty($sourceModelClass)) {
            return (string)$value;
        }

        try {
            $source = Mage::getModel($sourceModelClass);

            if ($source) {
                if ($sourceModelClass === 'eav/entity_attribute_source_table' || $source instanceof Mage_Eav_Model_Entity_Attribute_Source_Table) {
                    $attribute = Mage::getSingleton('eav/config')->getAttribute($entityTypeCode, $fieldCode);
                    if ($attribute && $attribute->getId()) {
                        $source->setAttribute($attribute);
                    }
                }

                $optionText = $source->getOptionText($value);

                if (is_array($optionText)) {
                    return implode(', ', $optionText);
                }

                if ($optionText !== false && $optionText !== '') {
                    return (string)$optionText;
                }
            }
        } catch (Throwable $e) {
            $this->debug('Error formatting source value for field ' . $fieldCode . ': ' . $e->getMessage());
        }

        return (string)$value;
    }

    /**
     * Detect execution context.
     *
     * @return string
     */
    public function detectContext(): string
    {
        if ($this->_isCli()) {
            return self::CONTEXT_BACKGROUND;
        }

        if (in_array(basename($this->_server('SCRIPT_NAME')), ['cron.php', 'api.php'], true)) {
            return self::CONTEXT_BACKGROUND;
        }

        if (Mage::app()->getStore()->isAdmin()) {
            return Mage::getSingleton('admin/session')->isLoggedIn()
                ? self::CONTEXT_ADMIN
                : self::CONTEXT_BACKGROUND;
        }

        if (Mage::app()->getRequest()->getModuleName() === 'api') {
            return self::CONTEXT_BACKGROUND;
        }

        return self::CONTEXT_FRONTEND;
    }

    /**
     * Check if current context is tracked.
     *
     * @return bool
     */
    public function isCurrentContextTracked(): bool
    {
        return $this->isContextCovered($this->getTrackingMode(), $this->detectContext());
    }

    /**
     * Check if context is covered by mode.
     *
     * @param string $mode
     * @param string $context
     * @return bool
     */
    public function isContextCovered(string $mode, string $context): bool
    {
        $source = Modestox_DeltaLog_Model_Adminhtml_System_Config_Source_TrackingMode::class;

        return match ($mode) {
            $source::MODE_EVERYWHERE => true,
            $source::MODE_ADMIN_BG   => $context !== self::CONTEXT_FRONTEND,
            default                  => $context === self::CONTEXT_ADMIN,
        };
    }

    /**
     * Check CLI mode.
     *
     * @return bool
     */
    protected function _isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * Get server variable.
     *
     * @param string $key
     * @return string
     */
    protected function _server(string $key): string
    {
        return (string)($_SERVER[$key] ?? '');
    }

    /**
     * Get retention days.
     *
     * @return int
     */
    public function getRetentionDays(): int
    {
        $days = Mage::getStoreConfig(self::XML_PATH_RETENTION_DAYS);

        return (is_numeric($days) && (int)$days > 0) ? (int)$days : 120;
    }
}
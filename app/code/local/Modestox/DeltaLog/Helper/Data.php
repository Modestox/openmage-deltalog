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

    /**
     * Formats option value using Source Model or Helper
     *
     * @param mixed  $value
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
                // Если это универсальный eav/entity_attribute_source_table, связываем его с атрибутом
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
     * Test helper method for option dictionaries
     *
     * @return array<string, string>
     */
    public function getTestStatusOptions(): array
    {
        return [
            '1'   => $this->__('Pending'),
            '2' => $this->__('Completed'),
        ];
    }
}
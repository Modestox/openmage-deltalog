<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Model_Rule_Field extends Mage_Core_Model_Abstract
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('modestox_deltalog/rule_field');
    }

    /**
     * Get custom options map from JSON or array
     *
     * @return array
     */
    public function getParsedCustomOptions(): array
    {
        $raw = $this->getData('custom_options');
        if (empty($raw)) {
            return [];
        }

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode((string)$raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Alias method for template getter compatibility
     *
     * @return array
     */
    public function getCustomOptions(): array
    {
        return $this->getParsedCustomOptions();
    }

    /**
     * Prepare custom options array from raw input string (custom_options_raw) before saving
     *
     * @return Modestox_DeltaLog_Model_Rule_Field
     */
    protected function _beforeSave(): self
    {
        parent::_beforeSave();

        if ($this->hasData('custom_options_raw')) {
            $rawString = trim((string)$this->getData('custom_options_raw'));
            $customOptions = [];

            if ($rawString !== '') {
                $items = explode(',', $rawString);

                foreach ($items as $item) {
                    $item = trim($item);
                    if ($item === '' || !str_contains($item, ':')) {
                        continue;
                    }

                    [$key, $value] = explode(':', $item, 2);
                    $cleanKey = trim($key);
                    $cleanVal = trim($value);

                    if ($cleanKey !== '' && $cleanVal !== '') {
                        $customOptions['v_' . $cleanKey] = $cleanVal;
                    }
                }
            }

            $this->setData('custom_options', !empty($customOptions) ? json_encode($customOptions, JSON_UNESCAPED_UNICODE) : null);
        }

        return $this;
    }

    /**
     * Trigger rule cache recompile on field delete
     *
     * @return Modestox_DeltaLog_Model_Rule_Field
     */
    protected function _afterDelete(): self
    {
        parent::_afterDelete();
        Mage::getModel('modestox_deltalog/compiler')->recompileRulesCache();

        return $this;
    }
}
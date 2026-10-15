<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Model_Adminhtml_System_Config_Source_TrackingMode
{
    public const MODE_ADMIN_ONLY = 'admin_only';
    public const MODE_ADMIN_BG = 'admin_bg';
    public const MODE_EVERYWHERE = 'everywhere';

    /**
     * Option array for system config.
     *
     * @return array<int, array<string, string>>
     */
    public function toOptionArray(): array
    {
        $helper = Mage::helper('modestox_deltalog');

        return [
            [
                'value' => self::MODE_ADMIN_ONLY,
                'label' => $helper->__('Admin Panel Only (Default)'),
            ],
            [
                'value' => self::MODE_ADMIN_BG,
                'label' => $helper->__('Admin + Background Processes (Cron, CLI, Imports, API)'),
            ],
            [
                'value' => self::MODE_EVERYWHERE,
                'label' => $helper->__('Everywhere (Admin, Background + Frontend)'),
            ],
        ];
    }
}
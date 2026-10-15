<?php
/**
 * Modestox DeltaLog for OpenMage
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/openmage-deltalog
 */

class Modestox_DeltaLog_Model_Cron
{
    /**
     * Clean old logs.
     *
     * @return void
     */
    public function cleanOldLogs(): void
    {
        /** @var Modestox_DeltaLog_Helper_Data $helper */
        $helper = Mage::helper('modestox_deltalog');

        if (!$helper->isEnabled()) {
            return;
        }

        $days = $helper->getRetentionDays();
        if ($days <= 0) {
            return;
        }

        $startTime = microtime(true);
        $startDate = date('Y-m-d H:i:s');

        try {
            $resource = Mage::getSingleton('core/resource');
            $connection = $resource->getConnection('core_write');
            $tableName = $resource->getTableName('modestox_deltalog/entry');

            $dateThreshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));

            $deletedRows = $connection->delete(
                $tableName,
                ['created_at < ?' => $dateThreshold],
            );

            $endTime = microtime(true);
            $endDate = date('Y-m-d H:i:s');
            $executionTime = number_format($endTime - $startTime, 4);

            $helper->debug(
                sprintf(
                    'Cron cleanup task finished. Started: %s | Finished: %s | Execution time: %s sec | Retention setting: %d day(s) | Threshold date: %s | Deleted entries: %d',
                    $startDate,
                    $endDate,
                    $executionTime,
                    $days,
                    $dateThreshold,
                    $deletedRows,
                ),
            );
        } catch (Throwable $e) {
            Mage::logException($e);
            $helper->debug('Cron cleanup error: ' . $e->getMessage());
        }
    }
}
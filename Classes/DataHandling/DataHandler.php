<?php

namespace SourceBroker\Hugo\DataHandling;

use SourceBroker\Hugo\Service\HugoExportService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataHandler implements SingletonInterface
{
    /**
     * Clears path and URL caches if the page was deleted.
     *
     * @param string $table
     * @param string|int $id
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public function processCmdmap_deleteAction($table, $id)
    {
        if (($table === 'pages' || $table === 'pages_language_overlay')) {
            $this->exportHugoPages();
        }
    }

    /**
     * Expires caches if the page was moved.
     *
     * @param string $command
     * @param string $table
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public function processCmdmap_postProcess($command, $table)
    {
        if ($command === 'move' && $table === 'pages') {
            $this->exportHugoPages();
        }
    }

    /**
     * A DataHandler hook to expire old records.
     *
     * @param string $status 'new' (ignoring) or 'update'
     * @param string $tableName
     * @param int $recordId
     * @param array $databaseData
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     * @return void
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public function processDatamap_afterDatabaseOperations(
        /** @noinspection PhpUnusedParameterInspection */
        $status,
        $tableName
    ) {
        if ($tableName === 'pages') {
            $this->exportHugoPages();
        }
    }

    /**
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public function exportHugoPages() {
        $hugoPageService = GeneralUtility::makeInstance(HugoExportService::class);
        $hugoPageService->exportTree();
    }
}

<?php

namespace SourceBroker\Hugo\DataHandling;

use SourceBroker\Hugo\Service\HugoExportContentService;
use SourceBroker\Hugo\Service\HugoExportPageService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class DataHandler implements SingletonInterface
{
    /**
     * Clears path and URL caches if the page was deleted.
     *
     * @param string $tableName
     * @param string|int $id
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public function processCmdmap_deleteAction($tableName, $id)
    {
        // TODO: optimize later
        $this->exportHugoPages();
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
        // TODO: optimize later
        $this->exportHugoPages();
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
        $tableName,
        $recordId,
        array $databaseData,
        /** @noinspection PhpUnusedParameterInspection */
        \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
    ) {
        if (!MathUtility::canBeInterpretedAsInteger($recordId)) {
            $recordId = (int)$dataHandler->substNEWwithIDs[$recordId];
        }
        switch ($tableName) {
            case 'pages':
                $this->exportHugoPages();
                break;
            case 'tt_content':
                $this->exportHugoContentElements($recordId);
                $this->exportHugoPages();
                break;
        }
    }

    /**
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public function exportHugoPages()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $hugoExportPageService = $objectManager->get(HugoExportPageService::class);
        $hugoExportPageService->exportAll();
    }

    public function exportHugoContentElements($contentRecordUid = null)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $hugoExportContentService = $objectManager->get(HugoExportContentService::class);
        if ($contentRecordUid === null) {
            $hugoExportContentService->exportAll();
        } else {
            $hugoExportContentService->exportSingle($contentRecordUid);
        }
    }
}

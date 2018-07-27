<?php

namespace SourceBroker\Hugo\DataHandling;

use SourceBroker\Hugo\Service\ExportContentService;
use SourceBroker\Hugo\Service\ExportPageService;
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
        if ($tableName === 'tt_content')
        {
            $this->deleteHugoContentElements((int)$id);
        }
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
    public function processCmdmap_postProcess($command, $tableName, $recordId, $value, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler, $pasteUpdate, $pasteDatamap)
    {
        if ($command === 'undelete') {
            switch ($tableName) {
                case 'pages':
                    $this->exportHugoPages();
                    break;
                case 'tt_content':
                    $this->exportHugoContentElements($recordId);
                    $this->exportHugoPages();
                    break;
            }
        } else {
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
        $hugoExportPageService = $objectManager->get(ExportPageService::class);
        $hugoExportPageService->exportAll();
    }

    /**
     * @param null $contentRecordUid
     *
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public function exportHugoContentElements($contentRecordUid = null)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $hugoExportContentService = $objectManager->get(ExportContentService::class);
        if ($contentRecordUid === null) {
            $hugoExportContentService->exportAll();
        } else {
            $hugoExportContentService->exportSingle($contentRecordUid);
        }
    }

    protected function deleteHugoContentElements(int $contentRecordUid): void
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $hugoExportContentService = $objectManager->get(ExportContentService::class);
        $hugoExportContentService->deleteSingle($contentRecordUid);
    }
}

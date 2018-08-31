<?php

namespace SourceBroker\Hugo\DataHandling;

use SourceBroker\Hugo\Queue\QueueInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
    public function processCmdmap_deleteAction($tableName, $recordId)
    {
        $queueValue = 'delete:'.$tableName;

        if (is_numeric($recordId)) {
            $queueValue .= ':'.intval($recordId);
        }

        $this->getQueue()->push($queueValue);
    }

    /**
     * Expires caches if the page was moved.
     *
     * @param string $command
     * @param $tableName
     * @param $recordId
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public function processCmdmap_postProcess($command, $tableName, $recordId, $value, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler, $pasteUpdate, $pasteDatamap)
    {
        $queueValue = 'update:'.$tableName;

        if (is_numeric($recordId)) {
            $queueValue .= ':'.$recordId;
        }

        $this->getQueue()->push($queueValue);
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

        $queueValue = 'update:'.$tableName;

        if (!MathUtility::canBeInterpretedAsInteger($recordId)) {
            $recordId = (int)$dataHandler->substNEWwithIDs[$recordId];
        }

        if (is_numeric($recordId)) {
            $queueValue .= ':'.$recordId;
        }

        $this->getQueue()->push($queueValue);
    }

    /**
     * @return QueueInterface
     */
    public function getQueue(): QueueInterface
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        return $objectManager->get(QueueInterface::class);
    }
}

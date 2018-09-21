<?php

namespace SourceBroker\Hugo\Task;

use SourceBroker\Hugo\Service\ExportContentService;
use SourceBroker\Hugo\Service\ExportMediaService;
use SourceBroker\Hugo\Service\ExportPageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ExportTask
 *
 * @package SourceBroker\Hugo\Task
 */
class ExportTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{

    /**
     * @return bool
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function execute()
    {
        if (
            (GeneralUtility::makeInstance(ExportContentService::class))->exportAll()
            && (GeneralUtility::makeInstance(ExportMediaService::class))->exportAll()
            && (GeneralUtility::makeInstance(ExportPageService::class))->exportAll()
        ) {
            return true;
        } else {
            return false;
        }
    }
}
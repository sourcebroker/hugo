<?php

namespace SourceBroker\Hugo\Task;

use SourceBroker\Hugo\Service\ExportContentService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ExportContentTask
 *
 * @package SourceBroker\Hugo\Task
 */
class ExportContentTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{

    /**
     * @return bool
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public function execute()
    {
        return GeneralUtility::makeInstance(ExportContentService::class)->exportAll();
    }
}
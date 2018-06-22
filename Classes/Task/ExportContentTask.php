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
     */
    public function execute()
    {
        return GeneralUtility::makeInstance(ExportContentService::class)
            ->exportAll();
    }
}
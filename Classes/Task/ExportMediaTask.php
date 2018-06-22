<?php

namespace SourceBroker\Hugo\Task;

use SourceBroker\Hugo\Service\HugoExportMediaService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ExportMediaTask
 *
 * @package SourceBroker\Hugo\Task
 */
class ExportMediaTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{

    /**
     * @return bool
     */
    public function execute()
    {
        return GeneralUtility::makeInstance(HugoExportMediaService::class)
            ->exportAll();
    }
}
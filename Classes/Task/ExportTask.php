<?php

namespace SourceBroker\Hugo\Task;

use SourceBroker\Hugo\Service\HugoExportContentService;
use SourceBroker\Hugo\Service\HugoExportMediaService;
use SourceBroker\Hugo\Service\HugoExportPageService;
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
     */
    public function execute()
    {
        if (
            (GeneralUtility::makeInstance(HugoExportContentService::class))->exportAll()
            && (GeneralUtility::makeInstance(HugoExportMediaService::class))->exportAll()
            && (GeneralUtility::makeInstance(HugoExportPageService::class))->exportAll()
        ) {
            return true;
        } else {
            return false;
        }
    }
}
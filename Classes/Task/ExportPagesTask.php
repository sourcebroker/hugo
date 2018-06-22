<?php

namespace SourceBroker\Hugo\Task;

use SourceBroker\Hugo\Service\HugoExportPageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ExportPagesTask
 *
 * @package SourceBroker\Hugo\Task
 */
class ExportPagesTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{

    /**
     * @return bool
     */
    public function execute()
    {
        return GeneralUtility::makeInstance(HugoExportPageService::class)
            ->exportAll();
    }
}
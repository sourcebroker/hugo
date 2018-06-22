<?php

namespace SourceBroker\Hugo\Hooks;

use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use SourceBroker\Hugo\Service\ExportMediaService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ProcessHook
 *
 * @package SourceBroker\Hugo\Hooks
 */
class ProcessHook implements \TYPO3\CMS\Core\Utility\File\ExtendedFileUtilityProcessDataHookInterface
{

    /**
     * Post-process a file action.
     *
     * @param string $action The action
     * @param array $cmdArr The parameter sent to the action handler
     * @param array $result The results of all calls to the action handler
     * @param \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility $parentObject
     */
    public function processData_postProcessAction(
        $action,
        array $cmdArr,
        array $result,
        ExtendedFileUtility $parentObject
    ) {
        GeneralUtility::makeInstance(ExportMediaService::class)->exportAll();
    }
}
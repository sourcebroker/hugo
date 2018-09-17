<?php

namespace SourceBroker\Hugo\Command;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use SourceBroker\Hugo\Service\BuildService;
use SourceBroker\Hugo\Service\ExportContentService;
use SourceBroker\Hugo\Service\ExportMediaService;
use SourceBroker\Hugo\Service\ExportPageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class HugoCommandController
 *
 * @package SourceBroker\Hugo\Command
 */
class HugoCommandController extends CommandController
{

    /**
     * Generating pages / content / media for all TYPO3 tree roots
     * Command: hugo:export
     *
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     * @todo
     */
    public function exportCommand()
    {
        $this->outputLine('Generating pages / content / media for all TYPO3 tree roots.');
        if (
            (GeneralUtility::makeInstance(ExportContentService::class))->exportAll()
            && (GeneralUtility::makeInstance(ExportMediaService::class))->exportAll()
            && (GeneralUtility::makeInstance(ExportPageService::class))->exportAll()
        ) {
            $this->outputLine('Success.');
        } else {
            $this->outputLine('Fail.');
        }
    }

    /**
     * Generating Hugo pages for all TYPO3 tree roots
     * Command: hugo:exportpages
     *
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     * @todo
     */
    public function exportPagesCommand()
    {
        $this->outputLine('Generating Hugo pages for all TYPO3 tree roots.');
        if ((GeneralUtility::makeInstance(ExportPageService::class))->exportAll()) {
            $this->outputLine('Success.');
        } else {
            $this->outputLine('Fail.');
        }
    }

    /**
     * Generating Hugo content for all TYPO3 tree roots
     * Command: hugo:exportcontent
     *
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     * @todo
     */
    public function exportContentCommand()
    {
        $hugoExportContentService = GeneralUtility::makeInstance(ExportContentService::class);
        $this->outputLine('Generating Hugo content for all TYPO3 tree roots.');

        if ($hugoExportContentService->exportAll()) {
            $this->outputLine('Success.');
        } else {
            $this->outputLine('Fail.');
        }
    }

    /**
     * Generating Hugo media for all TYPO3 tree roots
     * Command: hugo:exportmedia
     */
    public function exportMediaCommand()
    {
        /** @var \SourceBroker\Hugo\Service\ExportMediaService $service */
        $service = GeneralUtility::makeInstance(ExportMediaService::class);
        $this->outputLine('Generating Hugo media for all TYPO3 tree roots.');

        foreach ($service->exportAll() as $result) {
            $this->displayCommandResult($result);
        }
    }

    /**
     * Hugo build for all TYPO3 tree roots
     * Command: hugo:build
     *
     * @throws \Exception
     */
    public function buildCommand()
    {
        /** @var \SourceBroker\Hugo\Service\BuildService $service */
        $service = GeneralUtility::makeInstance(BuildService::class);
        $this->outputLine('Hugo build for all TYPO3 tree roots.');

        foreach ($service->buildAll() as $result) {
            $this->displayCommandResult($result);
        }
    }

    /**
     * @param \SourceBroker\Hugo\Domain\Model\ServiceResult $result
     */
    protected function displayCommandResult(\SourceBroker\Hugo\Domain\Model\ServiceResult $result) {
        $this->outputLine("Command: " . $result->getCommand());
        $this->outputLine("Output: " . $result->getCommandOutput());
        $this->outputLine("Success: " . ($result->isExecutedSuccessfully() ? 'true' : 'false'));
        if ($result->getMessage()) {
            $this->outputLine("Message: " . $result->getMessage());
        }
        echo $this->outputLine("\n" . str_repeat('-', 80) . "\n");
    }
}

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
 */
class HugoCommandController extends CommandController
{

    /**
     * Generating pages / content / media for all TYPO3 tree roots
     * Command: hugo:export
     *
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function exportCommand()
    {
        $summaryStatus = true;
        /** @var \SourceBroker\Hugo\Service\ExportContentService $contentService */
        $contentService = GeneralUtility::makeInstance(ExportContentService::class);
        /** @var \SourceBroker\Hugo\Service\ExportMediaService $mediaService */
        $mediaService = GeneralUtility::makeInstance(ExportMediaService::class);
        /** @var \SourceBroker\Hugo\Service\ExportPageService $pageService */
        $pageService = GeneralUtility::makeInstance(ExportPageService::class);
        $this->outputLine($this->decorateLabel('Generating pages / content / media for all TYPO3 tree roots'));
        $this->outputLine($this->decorateLabel('Export content elements:'));
        foreach ($contentService->exportAll() as $result) {
            $this->displayCommandResult($result);
            if (!$result->isExecutedSuccessfully()) {
                $summaryStatus = false;
            }
        }

        $this->outputLine($this->decorateLabel('Export media:'));
        foreach ($mediaService->exportAll() as $result) {
            $this->displayCommandResult($result);
            if (!$result->isExecutedSuccessfully()) {
                $summaryStatus = false;
            }
        }

        $this->outputLine($this->decorateLabel('Export pages:'));
        foreach ($pageService->exportAll() as $result) {
            $this->displayCommandResult($result);
            if (!$result->isExecutedSuccessfully()) {
                $summaryStatus = false;
            }
        }

        $this->outputLine('Summary status: ' . ($summaryStatus ? 'Success' : 'Fail'));
    }

    /**
     * Generating Hugo pages for all TYPO3 tree roots
     * Command: hugo:exportpages
     *
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function exportPagesCommand()
    {
        /** @var \SourceBroker\Hugo\Service\ExportPageService $service */
        $service = GeneralUtility::makeInstance(ExportPageService::class);
        $this->outputLine('Generating Hugo pages for all TYPO3 tree roots.');

        foreach ($service->exportAll() as $result) {
            $this->displayCommandResult($result);
        }
    }

    /**
     * Generating Hugo content for all TYPO3 tree roots
     * Command: hugo:exportcontent
     *
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public function exportContentCommand()
    {
        /** @var \SourceBroker\Hugo\Service\ExportContentService $hugoExportContentService */
        $service = GeneralUtility::makeInstance(ExportContentService::class);
        $this->outputLine('Generating Hugo content for all TYPO3 tree roots.');

        foreach ($service->exportAll() as $result) {
            $this->displayCommandResult($result);
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
    protected function displayCommandResult(
        \SourceBroker\Hugo\Domain\Model\ServiceResult $result
    ) {
        $this->outputLine('Command: ' . $result->getCommand());
        $this->outputLine('Output: ' . $result->getCommandOutput());
        $this->outputLine('Success: ' . ($result->isExecutedSuccessfully() ? 'true' : 'false'));
        if ($result->getMessage()) {
            $this->outputLine('Message: ' . $result->getMessage());
        }
        echo $this->outputLine("\n" . str_repeat('-', 80) . "\n");
    }

    /**
     * @param string $label
     * @param string $sign
     *
     * @return string
     */
    protected function decorateLabel($label, $sign = '#'): string
    {
        return str_repeat($sign, 20) . ' ' . $label . ' ' . str_repeat($sign, 20);
    }
}

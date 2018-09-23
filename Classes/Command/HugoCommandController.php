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

use SourceBroker\Hugo\Domain\Model\ServiceResult;
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
     * @throws \TYPO3\CMS\Core\Exception
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function exportCommand()
    {
        $this->outputLine();
        $this->outputLine('<fg=yellow;options=bold>[INFO]</>');
        $this->outputLine('Generating media / content / pages for all TYPO3 tree roots.');
        $this->outputLine('Below output of three commands:');
        $this->outputLine('1) hugo:exportmedia');
        $this->outputLine('2) hugo:exportcontent');
        $this->outputLine('3) hugo:exportpages');
        $this->outputLine();
        $this->outputLine('[1]' . str_repeat('-', 80));
        $this->exportMediaCommand();
        $this->outputLine("\n[2]" . str_repeat('-', 80));
        $this->exportContentCommand();
        $this->outputLine("\n[3]" . str_repeat('-', 80));
        $this->exportPagesCommand();
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
        /** @var \SourceBroker\Hugo\Service\ExportPageService $exportPagesService */
        $exportPagesService = GeneralUtility::makeInstance(ExportPageService::class);
        $exportPagesServiceResult = $exportPagesService->exportAll();
        $this->displayCommandResult('Generating Hugo pages.', $exportPagesServiceResult);
        return $exportPagesServiceResult->isExecutedSuccessfully();
    }

    /**
     * Generating Hugo content for all TYPO3 tree roots
     * Command: hugo:exportcontent
     *
     * @throws \TYPO3\CMS\Core\Exception
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public function exportContentCommand()
    {
        /** @var \SourceBroker\Hugo\Service\ExportContentService $exportContentService */
        $exportContentService = GeneralUtility::makeInstance(ExportContentService::class);
        $exportContentServiceResult = $exportContentService->exportAll();
        $this->displayCommandResult('Generating Hugo content.', $exportContentServiceResult);
        return $exportContentServiceResult->isExecutedSuccessfully();
    }

    /**
     * Generating Hugo media for all TYPO3 tree roots
     * Command: hugo:exportmedia
     */
    public function exportMediaCommand()
    {
        /** @var \SourceBroker\Hugo\Service\ExportMediaService $exportMediaService */
        $exportMediaService = GeneralUtility::makeInstance(ExportMediaService::class);
        $exportMediaServiceResult = $exportMediaService->exportAll();
        $this->displayCommandResult('Generating Hugo media.', $exportMediaServiceResult);
        return $exportMediaServiceResult->isExecutedSuccessfully();
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
        $buildService = GeneralUtility::makeInstance(BuildService::class);
        $buildServiceResult = $buildService->buildAll();
        $this->displayCommandResult('Hugo build for all TYPO3 tree roots.', $buildServiceResult);
        return $buildServiceResult->isExecutedSuccessfully();
    }

    /**
     * @param string|null $commandDescription
     * @param \SourceBroker\Hugo\Domain\Model\ServiceResult $result
     */
    protected function displayCommandResult(string $commandDescription = null, ServiceResult $result)
    {
        if (!empty($commandDescription)) {
            $this->outputLine();
            $this->outputLine('<fg=yellow;options=bold>[INFO]</>');
            $this->outputLine($commandDescription);
        }
        $this->outputLine();
        $this->outputLine('<fg=yellow;options=bold>[COMMAND STATUS]</> ');
        $this->outputLine(($result->isExecutedSuccessfully() ? 'Success.' : '<error>Failed.</error>'));

        if (!empty($result->getCommand())) {
            $this->outputLine();
            $this->outputLine('<fg=yellow;options=bold>[COMMAND EXECUTED]</>');
            $this->outputLine($result->getCommand());
        }
        if (!empty($result->getCommandOutput())) {
            $this->outputLine();
            $this->outputLine('<fg=yellow;options=bold>[COMMAND OUTPUT]</>');
            $this->outputLine($result->getCommandOutput());
        }
        if (!empty($result->getMessage())) {
            $this->outputLine();
            $this->outputLine('<fg=yellow;options=bold>[COMMAND MESSAGE]</> ');
            $this->outputLine($result->getMessage());
        }
    }
}

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
     * Export TYPO3 media / content / pages
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
     * Export TYPO3 pages
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
     * Export TYPO3 content
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
     * Export TYPO3 media
     */
    public function exportMediaCommand()
    {
        $hugoExportMediaService = GeneralUtility::makeInstance(ExportMediaService::class);
        $this->outputLine('Generating Hugo media for all TYPO3 tree roots.');

        if ($hugoExportMediaService->exportAll()) {
            $this->outputLine('Success.');
        } else {
            $this->outputLine('Fail.');
        }
    }

    /**
     * Build dist
     */
    public function buildCommand()
    {
        $buildService = GeneralUtility::makeInstance(BuildService::class);
        $this->outputLine('Generating Hugo build for all TYPO3 tree roots.');

        if ($buildService->buildAll()) {
            $this->outputLine('Success.');
        } else {
            $this->outputLine('Fail.');
        }
    }
}

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

use SourceBroker\Hugo\Service\HugoExportContentService;
use SourceBroker\Hugo\Service\HugoExportMediaService;
use SourceBroker\Hugo\Service\HugoExportPageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class HugoCommandController
 */
class HugoCommandController extends CommandController
{

    /**
     * Export all
     */
    public function exportCommand()
    {
        $this->outputLine('Generating pages / content / media for all TYPO3 tree roots.');
        if (
            (GeneralUtility::makeInstance(HugoExportContentService::class))->exportAll()
            && (GeneralUtility::makeInstance(HugoExportMediaService::class))->exportAll()
            && (GeneralUtility::makeInstance(HugoExportPageService::class))->exportAll()
        ) {
            $this->outputLine('Success.');
        } else {
            $this->outputLine('Fail.');
        }
    }

    /**
     * Export pages
     */
    public function exportPagesCommand()
    {
        $this->outputLine('Generating Hugo pages for all TYPO3 tree roots.');
        if ((GeneralUtility::makeInstance(HugoExportPageService::class))->exportAll()) {
            $this->outputLine('Success.');
        } else {
            $this->outputLine('Fail.');
        }
    }

    /**
     * Export content
     */
    public function exportContentCommand()
    {
        $hugoExportContentService = GeneralUtility::makeInstance(HugoExportContentService::class);
        $this->outputLine('Generating Hugo content for all TYPO3 tree roots.');

        if ($hugoExportContentService->exportAll()) {
            $this->outputLine('Success.');
        } else {
            $this->outputLine('Fail.');
        }
    }


    /**
     * Export media
     */
    public function exportMediaCommand()
    {
        $hugoExportMediaService = GeneralUtility::makeInstance(HugoExportMediaService::class);
        $this->outputLine('Generating Hugo media for all TYPO3 tree roots.');

        if ($hugoExportMediaService->exportAll()) {
            $this->outputLine('Success.');
        } else {
            $this->outputLine('Fail.');
        }
    }
}

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

use SourceBroker\Hugo\Service\HugoExportService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class HugoCommandController
 */
class HugoCommandController extends CommandController
{
    /**
     * Export content to hugo
     */
    public function exportCommand()
    {
        $hugoPageService = GeneralUtility::makeInstance(HugoExportService::class);
        $this->outputLine('Generating hugo pages for all TYPO3 tree roots.');

        if ($hugoPageService->exportTree()) {
            $this->outputLine('Success.');
        } else {
            $this->outputLine('Fail.');
        }
    }
}

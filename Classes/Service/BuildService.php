<?php

/***************************************************************
 *  Copyright notice
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace SourceBroker\Hugo\Service;

use SourceBroker\Hugo\Configuration\Configurator;
use SourceBroker\Hugo\Domain\Repository\Typo3PageRepository;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ExportContentService
 * @package SourceBroker\Hugo\Service
 */
class BuildService extends AbstractService
{
    /**
     * @return bool
     * @throws \Exception
     */
    public function buildAll(): bool
    {
        $results = [];
        foreach (($this->objectManager->get(Typo3PageRepository::class))->getSiteRootPages() as $siteRoot) {
            $results[] = $this->buildSingle($siteRoot['uid']);
        }
        return count(array_unique($results)) === 1 && end($results) === true;
    }

    public function buildSingle(int $rootPageUid): bool
    {
        $this->createLocker('hugoBuildDist');

        $hugoConfigForRootSite = Configurator::getByPid($rootPageUid);
        if ($hugoConfigForRootSite->getOption('enable')) {
            $hugoPathBinary = $hugoConfigForRootSite->getOption('hugo.path.binary');
            if (!empty($hugoPathBinary)) {
                exec($hugoPathBinary . ' ' . str_replace(['{PATH_site}'], [PATH_site],
                        $hugoConfigForRootSite->getOption('hugo.command')), $output, $return);

            } else {
                throw new \Exception('Can not find hugo binary');
            }
        }

        return $this->release();
    }

}

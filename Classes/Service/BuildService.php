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

/**
 * Class ExportContentService
 * @package SourceBroker\Hugo\Service
 */
class BuildService extends AbstractService
{
    /**
     * @return array
     * @throws \Exception
     */
    public function buildAll(): array
    {
        $results = [];
        foreach ($this->objectManager->get(Typo3PageRepository::class)->getSiteRootPages() as $siteRoot) {
            $results[] = $this->buildSingle($siteRoot['uid']);
        }

        return $results;
    }

    /**
     * @param int $rootPageUid
     *
     * @return \SourceBroker\Hugo\Domain\Model\ServiceResult
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public function buildSingle(int $rootPageUid): \SourceBroker\Hugo\Domain\Model\ServiceResult
    {
        $this->createLocker('hugoBuildDist');
        $hugoConfigForRootSite = Configurator::getByPid($rootPageUid);
        $serviceResult = $this->createServiceResult();

        if ($hugoConfigForRootSite->getOption('enable')) {
            $hugoPathBinary = $hugoConfigForRootSite->getOption('hugo.path.binary');
            if (!empty($hugoPathBinary)) {
                $serviceResult->setCommand($hugoPathBinary . ' ' . str_replace(['{PATH_site}'], [PATH_site],
                        $hugoConfigForRootSite->getOption('hugo.command')));
                $this->executeServiceResultCommand($serviceResult);
            } else {
                $serviceResult->setMessage('Can\'t find hugo binary #1535713956');
            }
        }

        $this->release();
        return $serviceResult;
    }
}

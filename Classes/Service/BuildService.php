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
use SourceBroker\Hugo\Domain\Model\ServiceResult;
use SourceBroker\Hugo\Domain\Repository\Typo3PageRepository;

/**
 * Class ExportContentService
 */
class BuildService extends AbstractService
{
    /**
     * @return array
     * @throws \Exception
     */
    public function buildAll(): ServiceResult
    {
        $serviceMessage = '';
        $commandOutput = [];
        $failed = 0;
        $serviceResult = $this->createServiceResult();
        foreach ($this->objectManager->get(Typo3PageRepository::class)->getSiteRootPages() as $siteRoot) {
            $singleServiceResult = $this->buildSingle($siteRoot['uid']);
            $serviceMessage .= $singleServiceResult->getMessage();
            $commandOutput[] = 'Command output when generating page tree for root site with uid: ' . $siteRoot['uid'] . "\n" . $singleServiceResult->getCommandOutput();
            if (!$singleServiceResult->isExecutedSuccessfully()) {
                $failed++;
            }
        }
        if ($failed === 0) {
            $serviceResult->setExecutedSuccessfully(true);
        }
        $serviceResult->setCommandOutput(implode("\n", $commandOutput));
        $serviceResult->setMessage($serviceMessage);
        return $serviceResult;
    }

    /**
     * @param int $rootPageUid
     *
     * @return \SourceBroker\Hugo\Domain\Model\ServiceResult
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public function buildSingle(int $rootPageUid): ServiceResult
    {
        $this->createLocker('BuildService_buildSingle');
        $serviceMessage = '';
        $hugoConfigForRootSite = Configurator::getByPid($rootPageUid);
        $serviceResult = $this->createServiceResult();
        if ($hugoConfigForRootSite->getOption('enable')) {
            $hugoPathBinary = $hugoConfigForRootSite->getOption('hugo.path.binary');
            if (!empty($hugoPathBinary)) {
                $serviceResult->setCommand($hugoPathBinary . ' ' . str_replace(['{PATH_site}'], [PATH_site],
                        $hugoConfigForRootSite->getOption('hugo.command')));
                $this->executeServiceResultCommand($serviceResult);
                if ($serviceResult->isExecutedSuccessfully()) {
                    $serviceMessage .= 'Generated successfully for root page with uid: ' . $rootPageUid . "\n";
                } else {
                    $serviceMessage .= 'Failed to generate for root page with uid: ' . $rootPageUid . "\n";
                }
            } else {
                $serviceMessage = 'Can\'t find hugo binary #1535713956';
            }
            $serviceResult->setMessage($serviceMessage);
        }
        $this->release();
        return $serviceResult;
    }
}

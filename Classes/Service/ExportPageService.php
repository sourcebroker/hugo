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
use SourceBroker\Hugo\Traversing\TreeTraverser;

/**
 * Class ExportPageService
 *
 * @package SourceBroker\Hugo\Service
 */
class ExportPageService extends AbstractService
{

    /**
     * @return \SourceBroker\Hugo\Domain\Model\ServiceResult[]
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function exportAll(): array
    {
        $this->createLocker('hugoExportPages');
        $results = [];

        foreach ($this->objectManager->get(Typo3PageRepository::class)->getSiteRootPages() as $siteRoot) {
            $hugoConfigForRootSite = Configurator::getByPid((int)$siteRoot['uid']);
            if ($hugoConfigForRootSite->getOption('enable')) {
                $writer = $this->objectManager->get($hugoConfigForRootSite->getOption('writer.class'));

                /** @var \SourceBroker\Hugo\Traversing\TreeTraverser $treeTraverser */
                $treeTraverser = $this->objectManager->get(TreeTraverser::class);
                $writer->setRootPath($hugoConfigForRootSite->getOption('writer.path.content'));
                $writer->setExcludeCleaningFolders([$hugoConfigForRootSite->getOption('writer.path.media')]);
                $treeTraverser->setWriter($writer);
                $treeTraverser->start(
                    $siteRoot['uid'],
                    [],
                    'getDocumentsForPage'
                );
            }
        }

        $this->release();
        return $results;
    }
}

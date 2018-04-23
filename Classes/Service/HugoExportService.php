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
use SourceBroker\Hugo\Traversing\PageTraverser;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class HugoExportService
{
    public function exportTree(int $entryPoint) : bool
    {
        $lockFactory = GeneralUtility::makeInstance(LockFactory::class);
        $locker = $lockFactory->createLocker(
            'hugo',
            LockingStrategyInterface::LOCK_CAPABILITY_SHARED | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK
        );
        do {
            try {
                $locked = $locker->acquire(LockingStrategyInterface::LOCK_CAPABILITY_SHARED | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK);
            } catch (LockAcquireWouldBlockException $e) {
                usleep(100000); //100ms
                continue;
            }
            if ($locked) {
                break;
            }
        } while (true);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var $config Configurator */
        $config = $objectManager->get(Configurator::class);
        $writer = GeneralUtility::makeInstance($config->getOption('writer.class'));
        $writer->setRootPath($config->getOption('path.content'));

        $pageTraverse = $objectManager->get(PageTraverser::class);
        $pageTraverse->setWriter($writer);
        $pageTraverse->start($entryPoint, []);

        if ($locked) {
            $locker->release();
            $locker->destroy();
            return true;
        }

    }

}

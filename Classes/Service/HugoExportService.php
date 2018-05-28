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
use SourceBroker\Hugo\Traversing\TreeTraverser;
use Symfony\Component\Process\Exception\ProcessFailedException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Symfony\Component\Process\Process;

/**
 * Class HugoExportService
 * @package SourceBroker\Hugo\Service
 */
class HugoExportService
{
    /**
     * @return bool
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     * @throws \Exception
     */
    public function exportTree(): bool
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

        foreach ($this->getSiteRoots() as $siteRoot) {
            $config = $objectManager->get(Configurator::class, null, $siteRoot['uid']);
            if ($config->getOption('enable')) {
                /** @var $config Configurator */
                $writer = GeneralUtility::makeInstance($config->getOption('writer.class'));
                $treeTraverser = $objectManager->get(TreeTraverser::class);
                $writer->setRootPath($config->getOption('writer.path.content'));
                $writer->setExcludeCleaningFolders([$config->getOption('writer.path.media')]);
                $treeTraverser->setWriter($writer);
                $treeTraverser->start($siteRoot['uid'], []);
            }
        }
        // TODO: make Process working for finding hugo path and then executing hugo itself.
        // TODO: For now its working only for finding "which hugo". Then error on hugo build.
        // TODO: Funny is that if Process will get output of "which hugo" then exec() is not working.
//        $process = new Process('which hugo');
//        $process->mustRun();
//        if (!$process->isSuccessful()) {
//            throw new ProcessFailedException($process);
//        }
//        $hugoBinaryPath = $process->getOutput();
        $hugoBinaryPath = 'hugo';
        if (!empty($hugoBinaryPath)) {
            $command = $hugoBinaryPath . ' -s' . PATH_site . 'typo3conf/ext/local/Resources/Private/ClickdummyExport/site/ -d' . PATH_site . 'typo3conf/ext/local/Resources/Private/ClickdummyExport/dist';
            exec($command);
        } else {
            throw new \Exception('Can not find hugo binnary');
        }
        if ($locked) {
            $locker->release();
            $locker->destroy();
            return true;
        }
    }

    /**
     * @return array
     */
    public function getSiteRoots()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');

        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));

        return $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('is_siteroot', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
            )
            ->orderBy('sorting')
            ->execute()
            ->fetchAll();
    }
}

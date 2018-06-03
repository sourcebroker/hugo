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
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class HugoExportMediaService
 * @package SourceBroker\Hugo\Service
 */
class HugoExportMediaService
{
    /**
     * @return bool
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     * @throws \Exception
     */
    public function exportAll(): bool
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $lockFactory = $objectManager->get(LockFactory::class);
        $locker = $lockFactory->createLocker(
            'hugoExportMedia',
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

        // We assume config for exporting content is the same for all available site roots so take first available
        // site root which is enabled for hugo.
        foreach (($objectManager->get(Typo3PageRepository::class))->getSiteRootPages() as $siteRoot) {
            /** @var $hugoConfigForRootSite Configurator */
            $hugoConfigForRootSite = $objectManager->get(Configurator::class, null, $siteRoot['uid']);
            if ($hugoConfigForRootSite->getOption('enable')) {

                $folderToStore = rtrim(PATH_site . $hugoConfigForRootSite->getOption('writer.path.media'),
                    '\\/');
                if (!file_exists($folderToStore)) {
                    GeneralUtility::mkdir_deep($folderToStore);
                }

                /** @var $storageRepository StorageRepository */
                $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
                $allStorages = $storageRepository->findAll();

                $filesHugo = [];
                foreach ($allStorages as $storage) {
                    $folder = GeneralUtility::makeInstance(Folder::class, $storage, '/', 'All files');
                    $files = $storage->getFilesInFolder($folder, $start = 0, $maxNumberOfItems = 0,
                        $useFilters = false,
                        $recursive = true, $sort = 'name');
                    foreach ($files as $file) {
                        if (!$storage->isWithinProcessingFolder($file->getIdentifier())
                            && $file->getProperty('type') == 2) {
                            $filesHugo[] = [
                                'src' => $file->getPublicUrl(false),
                                'name' => $file->getUid(),
                            ];
                        }
                    }
                    // TODO - make it relative symlink
                    $symlinkStorageFolder = PATH_site . rtrim($hugoConfigForRootSite->getOption('writer.path.media'),
                            '\\/') . '/' . rtrim($storage->getConfiguration()['basePath'], '\\/');
                    $command = 'ln -s ' . rtrim(PATH_site . $storage->getConfiguration()['basePath'],
                            '\\/') . ' ' . $symlinkStorageFolder;
                    if (!file_exists($symlinkStorageFolder)) {
                        exec($command);
                    }
                }

                file_put_contents(
                    $folderToStore . '/index.md',
                    "---\n" . Yaml::dump(['resources' => $filesHugo], 100) . "---\n"
                );
                // Leave after first hugo enabled site root becase content elements are the same for all root sites.
                break;
            }
        }
        if ($locked) {
            $locker->release();
            $locker->destroy();
            return true;
        }
    }
}
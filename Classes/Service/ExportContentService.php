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
use SourceBroker\Hugo\Domain\Repository\Typo3ContentRepository;
use SourceBroker\Hugo\Domain\Repository\Typo3PageRepository;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class ExportContentService
 * @package SourceBroker\Hugo\Service
 */
class ExportContentService
{
    /**
     * TODO - optimize use of locker. Make service a singleton with common lock state.
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
            'hugoExportContent',
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
        $pageRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);
        // We assume config for exporting content is the same for all available site roots so take first available
        // site root which is enabled for hugo.
        foreach (($objectManager->get(Typo3PageRepository::class))->getSiteRootPages() as $siteRoot) {
            $hugoConfigForRootSite = Configurator::getByPid((int)$siteRoot['uid']);
            if ($hugoConfigForRootSite->getOption('enable')) {
                foreach (($objectManager->get(Typo3ContentRepository::class))->getAll() as $contentElement) {

                    if ($contentElement['sys_language_uid'] > 0) {
                        $contentElement =
                            $pageRepository->getRecordOverlay(
                                'tt_content', $contentElement, $contentElement['sys_language_uid'],
                                $hugoConfigForRootSite->getOption('sys_language_overlay')
                            );
                    }
                    $camelCaseClass = str_replace('_', '', ucwords($contentElement['CType'], '_'));
                    $classForCType = null;
                    foreach ($hugoConfigForRootSite->getOption('content.contentToClass.mapper') as $contentToClassMapper) {
                        if (preg_match('/' . $contentToClassMapper['ctype'] . '/', $camelCaseClass, $cTypeMateches)) {
                            $classForCType = preg_replace_callback(
                                "/\\{([0-9]+)\\}/",
                                function ($match) use ($cTypeMateches) {
                                    return $cTypeMateches[$match[1]];
                                },
                                $contentToClassMapper['class']
                            );
                            break;
                        }
                    }
                    if (!$objectManager->isRegistered($classForCType)) {
                        $classForCType = $hugoConfigForRootSite->getOption('content.contentToClass.fallbackContentElementClass');
                    }
                    $contentElementObject = $objectManager->get($classForCType);
                    $folderToStore = rtrim(PATH_site . $hugoConfigForRootSite->getOption('writer.path.data'),
                            DIRECTORY_SEPARATOR) . '/';
                    $filename = $contentElement['uid'] . '.yaml';
                    if(!file_exists($folderToStore)) {
                        GeneralUtility::mkdir_deep($folderToStore);
                    }
                    file_put_contents(
                        $folderToStore . $filename,
                        Yaml::dump($contentElementObject->getData($contentElement), 100)
                    );
                }
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

    /**
     * TODO - optimize use of locker. Make service a singleton with common lock state.
     * @param $contentElementUid
     * @return bool
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public function exportSingle(int $contentElementUid): bool
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $lockFactory = $objectManager->get(LockFactory::class);
        $locker = $lockFactory->createLocker(
            'hugoExportContent',
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
        /** @var PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);
        foreach (($objectManager->get(Typo3PageRepository::class))->getSiteRootPages() as $siteRoot) {
            $hugoConfigForRootSite = Configurator::getByPid((int)$siteRoot['uid']);
            if ($hugoConfigForRootSite->getOption('enable')) {
                $contentElement = $objectManager->get(Typo3ContentRepository::class)->getByUid($contentElementUid);
                if ($contentElement['sys_language_uid'] > 0) {
                    $contentElement =
                        $pageRepository->getRecordOverlay(
                            'tt_content', $contentElement, $contentElement['sys_language_uid'],
                            $hugoConfigForRootSite->getOption('sys_language_overlay')
                        );
                }
                //$row = $this->sys_page->getRecordOverlay('tt_content', $row, $basePageRow['_PAGES_OVERLAY_LANGUAGE'], $tsfe->sys_language_contentOL);
                $camelCaseClass = str_replace('_', '', ucwords($contentElement['CType'], '_'));
                $classForCType = null;
                foreach ($hugoConfigForRootSite->getOption('content.contentToClass.mapper') as $contentToClassMapper) {
                    if (preg_match('/' . $contentToClassMapper['ctype'] . '/', $camelCaseClass, $cTypeMateches)) {
                        $classForCType = preg_replace_callback(
                            "/\\{([0-9]+)\\}/",
                            function ($match) use ($cTypeMateches) {
                                return $cTypeMateches[$match[1]];
                            },
                            $contentToClassMapper['class']
                        );
                        break;
                    }
                }
                if (!$objectManager->isRegistered($classForCType)) {
                    $classForCType = $hugoConfigForRootSite->getOption('content.contentToClass.fallbackContentElementClass');
                }
                $contentElementObject = $objectManager->get($classForCType);
                $folderToStore = rtrim(PATH_site . $hugoConfigForRootSite->getOption('writer.path.data'),
                        DIRECTORY_SEPARATOR) . '/';
                $filename = $contentElement['uid'] . '.yaml';
                if(!file_exists($folderToStore)) {
                    GeneralUtility::mkdir_deep($folderToStore);
                }
                file_put_contents(
                    $folderToStore . $filename,
                    Yaml::dump($contentElementObject->getData($contentElement), 100)
                );

                // Leave after first hugo enabled site root because content elements are the same for all root sites.
                break;
            }
        }
        if ($locked) {
            $locker->release();
            $locker->destroy();
            return true;
        }
        return false;
    }

    /**
     * @param int $contentElementUid
     *
     * @return bool
     */
    public function deleteSingle(int $contentElementUid): bool
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $lockFactory = $objectManager->get(LockFactory::class);
        $locker = $lockFactory->createLocker(
            'hugoExportContent',
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
            $hugoConfigForRootSite = Configurator::getByPid((int)$siteRoot['uid']);
            if ($hugoConfigForRootSite->getOption('enable')) {
                $contentElement = $objectManager->get(Typo3ContentRepository::class)->getByUid($contentElementUid);

                if (!empty($contentElement)) {
                    $contentElementFilePath = rtrim(PATH_site . $hugoConfigForRootSite->getOption('writer.path.data'),
                            DIRECTORY_SEPARATOR) . '/'. $contentElement['uid'] . '.yaml';

                    if (file_exists($contentElementFilePath)) {
                        unlink($contentElementFilePath);
                    }

                    break;
                }
            }
        }

        if ($locked) {
            $locker->release();
            $locker->destroy();
            return true;
        }
        return false;

    }
}

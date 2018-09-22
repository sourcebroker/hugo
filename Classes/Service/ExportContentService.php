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
use SourceBroker\Hugo\Domain\Repository\Typo3ContentRepository;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class ExportContentService
 */
class ExportContentService extends AbstractService
{
    /**
     * Export all TYPO3 content elements
     *
     * @return ServiceResult
     * @throws \TYPO3\CMS\Core\Exception
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public function exportAll(): ServiceResult
    {
        $this->createLocker('ExportContentService');
        $serviceResult = $this->createServiceResult();
        $index = 0;
        $hugoFirstRootSiteConfig = Configurator::getFirstRootsiteConfig();
        if ($hugoFirstRootSiteConfig instanceof Configurator && (int)$hugoFirstRootSiteConfig->getOption('enable')) {
            foreach (($this->objectManager->get(Typo3ContentRepository::class))->getAll() as $contentElement) {
                $this->saveContentElement($contentElement, $hugoFirstRootSiteConfig);
                $index++;
            }
        }
        $serviceResult->setMessage($index . ' content elements have been exported to files.');
        $serviceResult->setExecutedSuccessfully(true);

        $this->release();
        return $serviceResult;
    }

    /**
     * Export single content element
     *
     * @param int $contentElementUid
     *
     * @return \SourceBroker\Hugo\Domain\Model\ServiceResult
     * @throws \TYPO3\CMS\Core\Exception
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public function exportSingle(int $contentElementUid): ServiceResult
    {
        $this->createLocker('ExportContentService');
        $serviceResult = $this->createServiceResult();
        $hugoFirstRootSiteConfig = Configurator::getFirstRootsiteConfig();
        if ($hugoFirstRootSiteConfig instanceof Configurator && (int)$hugoFirstRootSiteConfig->getOption('enable')) {
            $contentElement = $this->objectManager->get(Typo3ContentRepository::class)->getByUid($contentElementUid);
            $this->saveContentElement($contentElement, $hugoFirstRootSiteConfig);
        }
        $this->release();
        return $serviceResult;
    }

    /**
     * @param int $contentElementUid
     *
     * @return ServiceResult
     * @throws \TYPO3\CMS\Core\Exception
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public function deleteSingle(int $contentElementUid): ServiceResult
    {
        $this->createLocker('ExportContentService');
        $serviceResult = $this->createServiceResult();
        $hugoFirstRootSiteConfig = Configurator::getFirstRootsiteConfig();
        if ($hugoFirstRootSiteConfig instanceof Configurator && (int)$hugoFirstRootSiteConfig->getOption('enable')) {
            $contentElement = $this->objectManager->get(Typo3ContentRepository::class)->getByUid($contentElementUid);
            if (!empty($contentElement)) {
                $contentElementAbsolutePath = $this->getAbsolutePathToStoreContentElement($hugoFirstRootSiteConfig) . '/'
                    . $this->getFilenameToStoreContentElement($contentElementUid);
                if (file_exists($contentElementAbsolutePath)) {
                    unlink($contentElementAbsolutePath);
                }
            }
        }
        $this->release();
        return $serviceResult;
    }

    /**
     * Save single content element to yaml file
     *
     * @param $contentElement
     * @param $hugoFirstRootSiteConfig
     */
    protected function saveContentElement(array $contentElement, Configurator $hugoFirstRootSiteConfig)
    {
        if ($contentElement['sys_language_uid'] > 0) {
            /** @var PageRepository $pageRepository */
            $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
            $contentElement =
                $pageRepository->getRecordOverlay(
                    'tt_content', $contentElement, $contentElement['sys_language_uid'],
                    $hugoFirstRootSiteConfig->getOption('sys_language_overlay')
                );
        }
        $camelCaseClass = str_replace('_', '', ucwords($contentElement['CType'], '_'));
        $classForCType = null;
        if (is_array($hugoFirstRootSiteConfig->getOption('content.contentToClass.mapper'))) {
            foreach ($hugoFirstRootSiteConfig->getOption('content.contentToClass.mapper') as $contentToClassMapper) {
                if (preg_match('/' . $contentToClassMapper['ctype'] . '/', $camelCaseClass, $cTypeMateches)) {
                    $classForCType = preg_replace_callback(
                        '/\\{([0-9]+)\\}/',
                        function ($match) use ($cTypeMateches) {
                            return $cTypeMateches[$match[1]];
                        },
                        $contentToClassMapper['class']
                    );
                    break;
                }
            }
        }
        if (!$this->objectManager->isRegistered($classForCType)) {
            $classForCType = $hugoFirstRootSiteConfig->getOption('content.contentToClass.fallbackContentElementClass');
        }
        $contentElementObject = $this->objectManager->get($classForCType);
        $absolutePathToStoreContentElement = $this->getAbsolutePathToStoreContentElement($hugoFirstRootSiteConfig);
        if (!file_exists($absolutePathToStoreContentElement)) {
            GeneralUtility::mkdir_deep($absolutePathToStoreContentElement);
        }
        file_put_contents(
            $absolutePathToStoreContentElement . '/' . $this->getFilenameToStoreContentElement($contentElement['uid']),
            Yaml::dump($contentElementObject->getData($contentElement), 100)
        );
    }

    protected function getAbsolutePathToStoreContentElement(Configurator $hugoFirstRootSiteConfig)
    {
        return rtrim(PATH_site . (string)$hugoFirstRootSiteConfig->getOption('writer.path.data'), DIRECTORY_SEPARATOR);
    }

    protected function getFilenameToStoreContentElement(int $contentElementUid)
    {
        return $contentElementUid . '.yaml';
    }
}

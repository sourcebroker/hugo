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
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ExportMediaService
 *
 */
class ExportMediaService extends AbstractService
{
    /**
     * @return \SourceBroker\Hugo\Domain\Model\ServiceResult
     * @throws \TYPO3\CMS\Core\Exception
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \Exception
     */
    public function exportAll(): ServiceResult
    {
        $this->createLocker('hugoExportMedia');
        $hugoFirstRootSiteConfig = Configurator::getFirstRootsiteConfig();
        $serviceResult = $this->createServiceResult();
        if ($hugoFirstRootSiteConfig instanceof Configurator && (int)$hugoFirstRootSiteConfig->getOption('enable')) {
            $folderToStore = rtrim(
                PATH_site . $hugoFirstRootSiteConfig->getOption('writer.path.media'),
                '\\/'
            );
            if (!file_exists($folderToStore)) {
                GeneralUtility::mkdir_deep($folderToStore);
            }
            $storagesUids = GeneralUtility::trimExplode(',',
                (string)$hugoFirstRootSiteConfig->getOption('media.indexer.fileStorageIds'));
            if (empty(array_filter($storagesUids))) {
                $storagesUids = array_map(function ($storage) {
                    return $storage->getUid();
                }, GeneralUtility::makeInstance(StorageRepository::class)->findAll());
            };
            $filesHugo = [];
            foreach ($storagesUids as $storageUid) {
                $storage = GeneralUtility::makeInstance(StorageRepository::class)->findByUid($storageUid);
                $folder = GeneralUtility::makeInstance(Folder::class, $storage, '/', $storage->getName() . ' [uid:' . $storage->getUid() . ']');
                $files = $storage->getFilesInFolder(
                    $folder,
                    $start = 0,
                    $maxNumberOfItems = 0,
                    $useFilters = false,
                    $recursive = true,
                    $sort = 'name'
                );
                foreach ($files as $file) {
                    if (!$storage->isWithinProcessingFolder($file->getIdentifier())
                        && (int)$file->getProperty('type') === 2
                    ) {
                        $filesHugo[] = [
                            'src' => $file->getPublicUrl(false),
                            'name' => $file->getUid(),
                        ];
                    }
                }
                $symlinkStorageFolder = PATH_site . rtrim($hugoFirstRootSiteConfig->getOption('writer.path.media'),
                        '\\/') . '/' . rtrim($storage->getConfiguration()['basePath'], '\\/');
                if (!file_exists($symlinkStorageFolder)) {
                    // TODO - make it relative symlink
                    $command = 'ln -nfs ' . rtrim(PATH_site . $storage->getConfiguration()['basePath'],
                            '\\/') . ' ' . $symlinkStorageFolder;
                    exec($command, $out, $status);
                }
                if (!file_exists($symlinkStorageFolder)) {
                    throw new \Exception('Can not create symlink to storage.', 1537649774);
                }
            }
            $languages = $hugoFirstRootSiteConfig->getOption('languages');
            $languages = !is_array($languages) ? [0 => ''] : array_merge([0 => ''], $languages);
            foreach ($languages as $lang) {
                file_put_contents(
                    $folderToStore . '/index' . (!empty($lang) ? '.' . $lang : '') . '.md',
                    "---\n" . Yaml::dump(['resources' => $filesHugo], 100) . "---\n"
                );
            }
        }
        $serviceResult->setExecutedSuccessfully(true);
        $serviceResult->setMessage('Storage files created at "' . $hugoFirstRootSiteConfig->getOption('writer.path.media') . '"');

        $this->release();
        return $serviceResult;
    }
}

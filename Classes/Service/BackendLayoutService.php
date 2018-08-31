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

use SourceBroker\Hugo\Utility\RootlineUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Class Typo3UrlService
 *
 * @package SourceBroker\Hugo\Service
 */
class BackendLayoutService extends AbstractService implements SingletonInterface
{
    /**
     * @var array
     */
    protected static $localIdentifierCache = [];

    /**
     * @var array
     */
    protected static $localCache = [];

    /**
     * @param int $pageUid
     *
     * @return string
     */
    public function getIdentifierByPage(int $pageUid): string
    {
        if (!isset(self::$localIdentifierCache[$pageUid])) {
            $rootLine = ($this->objectManager->get(RootlineUtility::class, $pageUid))->get();

            $identifier = '';

            krsort($rootLine);
            foreach ($rootLine as $key => $page) {
                if ($pageUid == $page['uid'] && !empty($page['backend_layout'])) {
                    $identifier = $page['backend_layout'];
                    break;
                }
                if (!empty($page['backend_layout_next_level'])) {
                    $identifier = $page['backend_layout_next_level'];
                    break;
                }
            }

            self::$localIdentifierCache[$pageUid] = $identifier;
        }

        return self::$localIdentifierCache[$pageUid];
    }

    /**
     * @param int $pageUid
     *
     * @return array
     */
    public function getByPage(int $pageUid): array
    {
        if (!isset(self::$localCache[$pageUid])) {
            $identifier = $this->getIdentifierByPage($pageUid);

            if (!StringUtility::beginsWith($identifier, 'pagets__')) {
                throw new \InvalidArgumentException('Only backend layouts from PageTSConfig are supported at the moment.', 1534620796);
            }

            $pageTsConfig = $this->objectManager->get(TypoScriptService::class)
                ->convertTypoScriptArrayToPlainArray(BackendUtility::getPagesTSconfig($pageUid));

            $pageTsConfigIdentifier = str_replace('pagets__', '', $identifier);

            self::$localCache[$pageUid] = $pageTsConfig['mod']['web_layout']['BackendLayouts'][$pageTsConfigIdentifier] ?? [];
        }

        return self::$localCache[$pageUid];
    }

    /**
     * @param int $pageUid
     *
     * @return array
     */
    public function getColPosesByPage(int $pageUid)
    {
        return array_unique(
            array_map('intval', array_column($this->getColumnsByPage($pageUid), 'colPos'))
        );
    }

    /**
     * @param int $pageUid
     * @param int $slideLevel
     *
     * @return array
     */
    public function getColPosesByPageAndSlideLevel(int $pageUid, int $slideLevel)
    {
        $colPoses = [];

        foreach ($this->getColumnsByPage($pageUid) as $col) {
            $hugoSlideLevel = (int)($col['txHugoSlide'] ?? 0);

            if (isset($col['colPos']) && ($hugoSlideLevel === -1 || $hugoSlideLevel >= $slideLevel)) {
                $colPoses[] = (int)$col['colPos'];
            }
        }

        return $colPoses;
    }

    /**
     * @param int $pageUid
     *
     * @return array
     */
    protected function getColumnsByPage(int $pageUid): array
    {
        $backendLayout = $this->getByPage($pageUid);
        $rows = $backendLayout['config']['backend_layout']['rows'] ?? [];
        $columns = [];

        foreach ($rows as $row) {
            $columns = array_merge($columns, ($row['columns'] ?? []));
        }

        return $columns;
    }

}

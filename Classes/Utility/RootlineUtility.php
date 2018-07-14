<?php

namespace SourceBroker\Hugo\Utility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;


/**
 * A utility resolving and Caching the Rootline generation
 */
class RootlineUtility
{
    /**
     * @var int
     */
    protected $pageUid;

    /**
     * /**
     * @var int
     */
    protected $sysLanguageUid = 0;

    /**
     * @var array
     */
    protected static $localCache = [];

    /**
     * Fields to fetch when populating rootline data
     *
     * @var array
     */
    protected static $rootlineFields = [
        'pid',
        'uid',
        't3ver_oid',
        't3ver_wsid',
        't3ver_state',
        'title',
        'alias',
        'nav_title',
        'media',
        'layout',
        'hidden',
        'starttime',
        'endtime',
        'fe_group',
        'extendToSubpages',
        'doktype',
        'TSconfig',
        'tsconfig_includes',
        'is_siteroot',
        'mount_pid',
        'mount_pid_ol',
        'fe_login_mode',
        'backend_layout_next_level'
    ];

    /**
     * Rootline Context
     *
     * @var \TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected $pageContext;

    /**
     * @var string
     */
    protected $cacheIdentifier;

    /**
     * @var array
     */
    protected static $pageRecordCache = [];

    /**
     * @param int $uid
     * @throws \RuntimeException
     */
    public function __construct($uid, $sysLanguageUid)
    {
        $this->pageUid = (int)$uid;
        $this->sysLanguageUid = (int)$sysLanguageUid;
        $this->pageContext = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);
        self::$rootlineFields = array_merge(self::$rootlineFields,
            GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'], true));
        self::$rootlineFields = array_unique(self::$rootlineFields);
        $this->cacheIdentifier = $this->getCacheIdentifier();
    }


    /**
     * Constructs the cache Identifier
     *
     * @param int $otherUid
     * @return string
     */
    public function getCacheIdentifier($otherUid = null)
    {
        return implode('_', [
            $otherUid !== null ? (int)$otherUid : $this->pageUid,
            $this->languageUid,
        ]);
    }

    /**
     * Returns the actual rootline
     *
     * @return array
     */
    public function get()
    {
        if (!isset(static::$localCache[$this->cacheIdentifier])) {
            $page = $this->getRecordArray($this->pageUid);
            $parentUid = $page['pid'];
            $cacheTags = ['pageId_' . $page['uid']];
            if ($parentUid > 0) {
                /** @var $rootline \TYPO3\CMS\Core\Utility\RootlineUtility */
                $rootline = GeneralUtility::makeInstance(\SourceBroker\Hugo\Utility\RootlineUtility::class, $parentUid,
                    $this->sysLanguageUid);
                $rootline = $rootline->get();
                // retrieve cache tags of parent rootline
                foreach ($rootline as $entry) {
                    $cacheTags[] = 'pageId_' . $entry['uid'];
                    if ($entry['uid'] == $this->pageUid) {
                        throw new \RuntimeException('Circular connection in rootline for page with uid ' . $this->pageUid . ' found. Check your mountpoint configuration.',
                            1343464103);
                    }
                }
            } else {
                $rootline = [];
            }
            $rootline[] = $page;
            krsort($rootline);
            static::$localCache[$this->cacheIdentifier] = $rootline;
        }
        return static::$localCache[$this->cacheIdentifier];
    }

    /**
     * Queries the database for the page record and returns it.
     *
     * @param int $uid Page id
     * @throws \RuntimeException
     * @return array
     */
    protected function getRecordArray($uid)
    {
        $currentCacheIdentifier = $this->getCacheIdentifier($uid);
        if (!isset(self::$pageRecordCache[$currentCacheIdentifier])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $row = $queryBuilder->select(...self::$rootlineFields)
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->neq(
                        'doktype',
                        $queryBuilder->createNamedParameter(PageRepository::DOKTYPE_RECYCLER, \PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetch();
            if (empty($row)) {
                throw new \RuntimeException('Could not fetch page data for uid ' . $uid . '.', 1343589451);
            }
            if (is_array($row)) {
                if ($this->languageUid > 0) {
                    $row = $this->pageContext->getPageOverlay($row, $this->languageUid);
                }
                $row = $this->enrichWithRelationFields($row['_PAGES_OVERLAY_UID'] ?? $uid, $row);
                self::$pageRecordCache[$currentCacheIdentifier] = $row;
            }
        }
        if (!is_array(self::$pageRecordCache[$currentCacheIdentifier])) {
            throw new \RuntimeException('Broken rootline. Could not resolve page with uid ' . $uid . '.', 1343464101);
        }
        return self::$pageRecordCache[$currentCacheIdentifier];
    }

    /**
     * Resolve relations as defined in TCA and add them to the provided $pageRecord array.
     *
     * @param int $uid Either pages.uid or pages_language_overlay.uid if localized
     * @param array $pageRecord Page record (possibly overlaid) to be extended with relations
     * @throws \RuntimeException
     * @return array $pageRecord with additional relations
     */
    protected function enrichWithRelationFields($uid, array $pageRecord)
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        // @todo Remove this special interpretation of relations by consequently using RelationHandler
        foreach ($GLOBALS['TCA']['pages']['columns'] as $column => $configuration) {
            // Ensure that only fields defined in $rootlineFields (and "addRootLineFields") are actually evaluated
            if (array_key_exists($column, $pageRecord) && $this->columnHasRelationToResolve($configuration)) {
                $configuration = $configuration['config'];
                if ($configuration['MM']) {
                    /** @var $loadDBGroup \TYPO3\CMS\Core\Database\RelationHandler */
                    $loadDBGroup = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\RelationHandler::class);
                    $loadDBGroup->start(
                        $pageRecord[$column],
                        // @todo That depends on the type (group, select, inline)
                        isset($configuration['allowed']) ? $configuration['allowed'] : $configuration['foreign_table'],
                        $configuration['MM'],
                        $uid,
                        'pages',
                        $configuration
                    );
                    $relatedUids = isset($loadDBGroup->tableArray[$configuration['foreign_table']])
                        ? $loadDBGroup->tableArray[$configuration['foreign_table']]
                        : [];
                } else {
                    // @todo The assumption is wrong, since group can be used without "MM", but having "allowed"
                    $table = $configuration['foreign_table'];

                    $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
                    $queryBuilder->getRestrictions()->removeAll()
                        ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                        ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
                    $queryBuilder->select('uid')
                        ->from($table)
                        ->where(
                            $queryBuilder->expr()->eq(
                                $configuration['foreign_field'],
                                $queryBuilder->createNamedParameter(
                                    $uid,
                                    \PDO::PARAM_INT
                                )
                            )
                        );

                    if (isset($configuration['foreign_match_fields']) && is_array($configuration['foreign_match_fields'])) {
                        foreach ($configuration['foreign_match_fields'] as $field => $value) {
                            $queryBuilder->andWhere(
                                $queryBuilder->expr()->eq(
                                    $field,
                                    $queryBuilder->createNamedParameter($value, \PDO::PARAM_STR)
                                )
                            );
                        }
                    }
                    if (isset($configuration['foreign_table_field'])) {
                        $queryBuilder->andWhere(
                            $queryBuilder->expr()->eq(
                                trim($configuration['foreign_table_field']),
                                $queryBuilder->createNamedParameter(
                                    (int)$this->languageUid > 0 ? 'pages_language_overlay' : 'pages',
                                    \PDO::PARAM_STR
                                )
                            )
                        );
                    }
                    if (isset($configuration['foreign_sortby'])) {
                        $queryBuilder->orderBy($configuration['foreign_sortby']);
                    }
                    try {
                        $statement = $queryBuilder->execute();
                    } catch (DBALException $e) {
                        throw new \RuntimeException('Could to resolve related records for page ' . $uid . ' and foreign_table ' . htmlspecialchars($table),
                            1343589452);
                    }
                    $relatedUids = [];
                    while ($row = $statement->fetch()) {
                        $relatedUids[] = $row['uid'];
                    }
                }
                $pageRecord[$column] = implode(',', $relatedUids);
            }
        }
        return $pageRecord;
    }

    /**
     * Checks whether the TCA Configuration array of a column
     * describes a relation which is not stored as CSV in the record
     *
     * @param array $configuration TCA configuration to check
     * @return bool TRUE, if it describes a non-CSV relation
     */
    protected function columnHasRelationToResolve(array $configuration)
    {
        $configuration = $configuration['config'];
        if (!empty($configuration['MM']) && !empty($configuration['type']) && in_array($configuration['type'],
                ['select', 'inline', 'group'])) {
            return true;
        }
        if (!empty($configuration['foreign_field']) && !empty($configuration['type']) && in_array($configuration['type'],
                ['select', 'inline'])) {
            return true;
        }
        return false;
    }


    public function getSlugifiedRootline($withoutHome = true)
    {
        $typo3PageRepository = GeneralUtility::makeInstance(\SourceBroker\Hugo\Domain\Repository\Typo3PageRepository::class);
        $slugifier = GeneralUtility::makeInstance(\Cocur\Slugify\Slugify::class);

        $rootline = array_reverse($this->get());
        if(!empty($withoutHome)){
            array_shift($rootline);
        }

        $pathParts = [];
        foreach ($rootline as $key => $page) {
            if (!in_array($page['doktype'], [
                    PageRepository::DOKTYPE_SYSFOLDER,
                    PageRepository::DOKTYPE_SHORTCUT
                ]
            )) {
                $translation = $typo3PageRepository->getPageTranslation($page['uid'], 0);
                if (!empty($translation[0]['title'])) {
                    $pathParts[] = $slugifier->slugify(!empty($translation[0]['nav_title']) ? $translation[0]['nav_title'] : $translation[0]['title']);
                } else {
                    $pathParts[] = $slugifier->slugify(!empty($page['nav_title']) ? $page['nav_title'] : $page['title']);
                }
            }
        }
        return implode('/', $pathParts);

    }

}

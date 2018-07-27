<?php

namespace SourceBroker\Hugo\Domain\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Typo3PageRepository
{
    /**
     * @param int $uid
     * @return array
     */
    public function getByUid(int $uid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        return $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
    }

    /**
     * @return array
     */
    public function getSiteRootPages(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        return $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('is_siteroot',
                    $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting')
            ->execute()
            ->fetchAll();
    }

    /**
     * @param int $pageUid
     * @param int $sysLangaugeUid
     * @return array
     */
    public function getPageContentElements(int $pageUid, int $sysLangaugeUid = 0): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        return $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('pid',
                    $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('sys_language_uid',
                    $queryBuilder->createNamedParameter($sysLangaugeUid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting')
            ->execute()
            ->fetchAll();
    }

    /**
     * @param int $pageUid
     * @return array
     */
    public function getShortcutsPointingToPage(int $pageUid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        return $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('shortcut',
                    $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting')
            ->execute()
            ->fetchAll();
    }


    /**
     * @param int $pid
     * @param array $doktypes
     * @return array
     */
    public function getPagesByPidAndDoktype(int $pid, array $doktypes)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->select('uid', 'title')->from('pages')->where(
            $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)),
            $queryBuilder->expr()->in('doktype',
                $queryBuilder->createNamedParameter($doktypes, Connection::PARAM_INT_ARRAY))
        );
        return $queryBuilder->execute()->fetchAll();
    }


    /**
     * @param int $pid
     * @return array
     */
    public function getPageTranslations(int $defaultLangPageUid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages_language_overlay');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

        return $queryBuilder->select('*')
            ->from('pages_language_overlay')
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($defaultLangPageUid, \PDO::PARAM_INT)))
            ->execute()
            ->fetchAll();
    }

    /**
     * @param int $pid
     * @return array
     */
    public function getPageTranslation(int $defaultLangPageUid, int $sysLanguageUid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages_language_overlay');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

        return $queryBuilder->select('*')
            ->from('pages_language_overlay')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($defaultLangPageUid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($sysLanguageUid, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetchAll();
    }
}
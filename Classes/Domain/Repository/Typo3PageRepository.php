<?php

namespace SourceBroker\Hugo\Domain\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Typo3PageRepository
 *
 * @package SourceBroker\Hugo\Domain\Repository
 */
class Typo3PageRepository
{
    /**
     * @param int $uid
     * @return array
     */
    public function getByUid(int $uid): ?array
    {
        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable('pages');
        $result = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
        return $result === false ? null : $result;
    }

    /**
     * @return array
     */
    public function getSiteRootPages(): array
    {
        $queryBuilder = $this->getConnectionPool()
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
     * @param int $sysLanguageUid
     * @return array
     */
    public function getPageContentElements(int $pageUid, int $sysLanguageUid = 0): array
    {
        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable('tt_content');

        return $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('pid',
                    $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('sys_language_uid',
                    $queryBuilder->createNamedParameter($sysLanguageUid, \PDO::PARAM_INT)
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
        $queryBuilder = $this->getConnectionPool()
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
        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable('pages');
        $queryBuilder->select('uid', 'title')->from('pages')->where(
            $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)),
            $queryBuilder->expr()->in('doktype',
                $queryBuilder->createNamedParameter($doktypes, Connection::PARAM_INT_ARRAY))
        );
        return $queryBuilder->execute()->fetchAll();
    }


    /**
     * @param int $defaultLangPageUid
     * @return array
     */
    public function getPageTranslations(int $defaultLangPageUid): array
    {
        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable('pages_language_overlay');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

        return $queryBuilder->select('*')
            ->from('pages_language_overlay')
            ->where($queryBuilder->expr()->eq('pid',
                $queryBuilder->createNamedParameter($defaultLangPageUid, \PDO::PARAM_INT)))
            ->execute()
            ->fetchAll();
    }

    /**
     * @param int $defaultLangPageUid
     * @param int $sysLanguageUid
     * @return array
     */
    public function getPageTranslation(int $defaultLangPageUid, int $sysLanguageUid): array
    {
        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable('pages_language_overlay');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

        return $queryBuilder->select('*')
            ->from('pages_language_overlay')
            ->where(
                $queryBuilder->expr()->eq('pid',
                    $queryBuilder->createNamedParameter($defaultLangPageUid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid',
                    $queryBuilder->createNamedParameter($sysLanguageUid, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetchAll();
    }

    /**
     * @return \TYPO3\CMS\Core\Database\ConnectionPool
     */
    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
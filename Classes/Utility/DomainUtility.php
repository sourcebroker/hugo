<?php

namespace SourceBroker\Hugo\Utility;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DomainUtility
 */
class DomainUtility implements SingletonInterface
{

    /**
     * @param int $pid
     *
     * @return string[]
     */
    public function getHugoDomainsForPid(int $pid): array
    {
        return array_filter(
            (array)array_merge(
                ...array_map(
                    function ($commaSeparatedHugoDomains) {
                        return GeneralUtility::trimExplode(',', $commaSeparatedHugoDomains);
                    },
                    array_column($this->getDomainRecordsForRootLinePid($pid), 'tx_hugo_domains')
                )
            )
        );
    }

    /**
     * @param int $pid
     *
     * @return array
     */
    protected function getDomainRecordsForRootLinePid(int $pid): array
    {
        $rootLinePids = array_map(
            function ($rootLinePage) {
                return (int)$rootLinePage['uid'];
            },
            GeneralUtility::makeInstance(RootlineUtility::class, $pid)->get()
        );

        if (empty($rootLinePids)) {
            return [];
        }

        $qb = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_domain');

        return $qb->select('*')
            ->from('sys_domain')
            ->where($qb->expr()->orX(
                ...array_map(
                    function ($pid) use ($qb) {
                        return $qb->expr()->eq('pid', $qb->createNamedParameter($pid, \PDO::PARAM_INT));
                    },
                    $rootLinePids
                )
            ))
            ->execute()
            ->fetchAll();
    }
}

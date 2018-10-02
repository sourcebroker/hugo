<?php
declare(strict_types=1);

namespace SourceBroker\Hugo\Typolink;

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

use Cocur\Slugify\Slugify;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Builds a TypoLink to a database record
 */
class DatabaseRecordLinkBuilder extends AbstractTypolinkBuilder
{
    /**
     * @inheritdoc
     */
    public function build(array &$linkDetails, string $linkText, string $target, array $conf): array
    {
        $linkHandlerConfiguration = $this->txHugoConfigurator->getOption('record.indexer.exporter.' . $linkDetails['identifier']);

        $pageLinkBuilder = GeneralUtility::makeInstance(ObjectManager::class)->get(PageLinkBuilder::class, $this->txHugoConfigurator);

        $pageLinkDetails = ['pageuid' => $linkHandlerConfiguration['pageUid']];

        list($pageUri) = $pageLinkBuilder->build($pageLinkDetails, '', '', []);
        $record = $this->getRecordByUid($linkHandlerConfiguration['table'], (int)$linkDetails['uid']);
        // @todo make slug configurable and use same approach in \SourceBroker\Hugo\Indexer\RecordIndexer::getDocumentsForPage
        $recordUri = $record['uid'] . '_' . (new Slugify())->slugify($record['title']) . '/';

        return [
            $pageUri . $recordUri,
            $linkText,
            $target
        ];
    }

    /**
     * @param $table
     * @param $recordUid
     *
     * @return int
     */
    protected function getRecordByUid($table, $recordUid)
    {
        return ($qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table))
            ->select('*')
            ->from($table)
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($recordUid, \PDO::PARAM_INT)))
            ->execute()
            ->fetch();
    }
}

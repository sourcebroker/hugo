<?php
namespace SourceBroker\Hugo\Indexer;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use SourceBroker\Hugo\Domain\Model\Document;
use SourceBroker\Hugo\Domain\Model\DocumentCollection;

class RecordIndexer extends AbstractIndexer
{
    /**
     * @param int $pageUid
     * @param DocumentCollection $documentCollection
     * @return array
     */
    public function runCollection(int $pageUid, DocumentCollection $documentCollection): array
    {
        $table = 'tx_news_domain_model_news';

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->select('*')->from($table)->where(
            $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT))
        );
        $recordRows = $queryBuilder->execute()->fetchAll();

        foreach ($recordRows as $record) {
            $document = $documentCollection->create();

            $document->setId($record['uid']);
            $document->setTitle($record['title']);
            $document->setSlug($this->slugify($record['title']));

            //$document->setDescription($record['description']);
            //$document->setKeywords($record['keywords']);
            //$document->setDate($record['datetime']);
            //$document->setTeaser($record['teaser']);
            //$document->setBodytext($record['bodytext']);
            //$document->setDraft($record['hidden']);
            //$document->setEndtime($record['expirydate']);
        }

        return [
            $pageUid,
            $documentCollection
        ];
    }

}
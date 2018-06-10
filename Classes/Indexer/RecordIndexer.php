<?php

namespace SourceBroker\Hugo\Indexer;

use SourceBroker\Hugo\Configuration\Configurator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use SourceBroker\Hugo\Domain\Model\DocumentCollection;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class RecordIndexer extends AbstractIndexer
{
    /**
     * @param int $pageUid
     * @param DocumentCollection $documentCollection
     * @return array
     */
    public function getDocumentsForPage(int $pageUid, DocumentCollection $documentCollection): array
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $hugoConfig = $objectManager->get(Configurator::class, null, $pageUid);

        foreach($hugoConfig->getOption('indexer.records.exporter') as $exporterConfig) {
            if ($pageUid == $exporterConfig['pageUid']) {
                $table = $exporterConfig['table'];
                $recordsPid = $exporterConfig['recordsPid'];
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                $queryBuilder->select('*')->from($table)->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($recordsPid, \PDO::PARAM_INT))
                );
                $recordRows = $queryBuilder->execute()->fetchAll();

                foreach ($recordRows as $record) {
                    $slug = $this->slugify($record['title']);
                    $document = $documentCollection->create();
                    $document->setStoreFilename($record['uid'] . '_' . ucfirst($slug))
                        ->setId($record['uid'])
                        ->setTitle($record['title'])
                        ->setSlug($slug);

                    //$document->setDescription($record['description']);
                    //$document->setKeywords($record['keywords']);
                    //$document->setDate($record['datetime']);
                    //$document->setTeaser($record['teaser']);
                    //$document->setBodytext($record['bodytext']);
                    //$document->setDraft($record['hidden']);
                    //$document->setEndtime($record['expirydate']);
                }
            }
        }

        return [
            $pageUid,
            $documentCollection
        ];

    }

}
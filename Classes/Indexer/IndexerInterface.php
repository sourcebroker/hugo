<?php


namespace SourceBroker\Hugo\Indexer;

use SourceBroker\Hugo\Domain\Model\DocumentCollection;

interface IndexerInterface
{
    /**
     * @param int $pageUid
     * @param DocumentCollection $documentCollection
     * @return array
     */
    public function getDocumentsForPage(int $pageUid, DocumentCollection $documentCollection): array;
}

<?php


namespace SourceBroker\Hugo\Indexer;


use SourceBroker\Hugo\Domain\Model\Document;
use SourceBroker\Hugo\Domain\Model\DocumentCollection;

interface IndexerInterface
{
    /**
     * @param int $pageUid
     * @param Document $document
     * @return array
     */
    public function run(int $pageUid, Document $document): array;

    /**
     * @param int $pageUid
     * @param DocumentCollection $documentCollection
     * @return array
     */
    public function runCollection(int $pageUid, DocumentCollection $documentCollection): array;
}
<?php

namespace SourceBroker\Hugo\Indexer;

use Cocur\Slugify\Slugify;
use SourceBroker\Hugo\Domain\Model\Document;
use SourceBroker\Hugo\Domain\Model\DocumentCollection;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class AbstractIndexer
 *
 * @package SourceBroker\Hugo\Indexer
 */
class AbstractIndexer implements IndexerInterface, SingletonInterface
{

    /**
     * @var Slugify
     */
    protected $slugifier;

    /**
     * AbstractIndexer constructor.
     */
    public function __construct()
    {
        $this->slugifier = new Slugify();
    }

    /**
     * @param int $pageUid
     * @param Document $document
     * @return array
     */
    public function run(int $pageUid, Document $document): array
    {
        return [
            $pageUid,
            $document
        ];
    }

    /**
     * @param int $pageUid
     * @param DocumentCollection $documentCollection
     * @return array
     */
    public function runCollection(int $pageUid, DocumentCollection $documentCollection): array
    {
        return [
            $pageUid,
            $documentCollection
        ];
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function slugify(string $string)
    {
        return $this->slugifier->slugify($string);
    }
}
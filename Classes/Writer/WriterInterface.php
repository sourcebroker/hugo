<?php

namespace SourceBroker\Hugo\Writer;

use SourceBroker\Hugo\Domain\Model\Document;
use SourceBroker\Hugo\Domain\Model\DocumentCollection;

/**
 * Interface WriterInterface
 *
 * @package SourceBroker\Hugo\Writer
 */
interface WriterInterface
{

    /**
     * @param string $rootPath
     */
    public function setRootPath(string $rootPath): void;

    /**
     * @param Document $document
     * @param array $path
     */
    public function save(Document $document, array $path): void;

    /**
     * @param DocumentCollection $collection
     * @param array $path
     */
    public function saveDocuments(DocumentCollection $collection, array $path): void;

    /**
     * clean root path folder
     */
    public function clean(): void;
}
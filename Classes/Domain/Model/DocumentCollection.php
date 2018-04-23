<?php

namespace SourceBroker\Hugo\Domain\Model;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class DocumentCollection
 *
 * @package SourceBroker\Hugo\Domain\Model
 */
class DocumentCollection extends ObjectStorage
{

    /**
     * @return Document
     */
    public function create()
    {
        $document = new Document();
        $this->attach($document);

        return $document;
    }
}
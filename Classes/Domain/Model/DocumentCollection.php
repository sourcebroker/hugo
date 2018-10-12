<?php

namespace SourceBroker\Hugo\Domain\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class DocumentCollection
 *
 */
class DocumentCollection extends ObjectStorage
{
    /**
     * @return Document
     */
    public function create()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var \SourceBroker\Hugo\Domain\Model\Document $document */
        $document = $objectManager->get(Document::class);
        $this->attach($document);
        return $document;
    }

    /**
     * @param string $type
     * @param int $id
     * @return Document|null
     */
    public function getByTypeAndId(string $type, int $id)
    {
        /** @var Document $document */
        foreach ($this as $document) {
            if ($document->getId() !== $id || $document->getType() !== $type) {
                continue;
            }

            return $document;
        }

        return null;
    }
}

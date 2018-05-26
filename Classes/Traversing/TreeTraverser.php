<?php

namespace SourceBroker\Hugo\Traversing;

use SourceBroker\Hugo\Domain\Model\Document;
use SourceBroker\Hugo\Domain\Model\DocumentCollection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Class PageTraverser
 *
 * @package SourceBroker\Hugo\Traversing
 */
class TreeTraverser
{
    /**
     * @var \SourceBroker\Hugo\Writer\YamlWriter
     */
    protected $writer;

    public function setWriter($writer)
    {
        $this->writer = $writer;
        $this->writer->clean();
    }


    /**
     * @param int $pageUid
     * @param string[] $path
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function start(int $pageUid, array $path = []): void
    {
        $signalDispatcher = GeneralUtility::makeInstance(Dispatcher::class);

        $document = new Document();
        $document->setType(Document::TYPE_PAGE);

        $signalDispatcher->dispatch(__CLASS__, 'document', [
            $pageUid,
            $document,
        ]);

        if ($document->getDeleted()) {
            return;
        }

        if (!$document->isRoot()) {
            $path[] = $document->getSlug();
        }

        $this->writer->save($document, $path);

        $documentCollection = new DocumentCollection();

        $signalDispatcher->dispatch(__CLASS__, 'extractDocuments', [
            $pageUid,
            $documentCollection,
        ]);

        $this->writer->saveDocuments($documentCollection, $path);

        $pages = $this->getPages($pageUid);

        foreach ($pages as $page) {
            $this->start($page['uid'], $path);
        }
    }

    /**
     * @param int $pid
     * @return array
     */
    protected function getPages(int $pid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->select('uid', 'title')->from('pages')->where(
            $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT))
        );

        return $queryBuilder->execute()->fetchAll();
    }
}
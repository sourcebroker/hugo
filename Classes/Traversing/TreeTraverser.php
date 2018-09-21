<?php

namespace SourceBroker\Hugo\Traversing;

use Cocur\Slugify\Slugify;
use SourceBroker\Hugo\Domain\Model\DocumentCollection;
use SourceBroker\Hugo\Domain\Repository\Typo3PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class PageTraverser
 *
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
     * @param string $dispatchMethod
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function start(int $pageUid, array $path = [], $dispatchMethod): void
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $typo3PageRepository = $objectManager->get(Typo3PageRepository::class);
        $typo3Page = $typo3PageRepository->getByUid($pageUid);

        if (!$typo3Page['is_siteroot'] && $typo3Page['doktype'] !== PageRepository::DOKTYPE_SYSFOLDER) {
            $path[] = ($objectManager->get(Slugify::class))->slugify($typo3Page['nav_title'] ?: $typo3Page['title']);
        }

        $documentCollection = $objectManager->get(DocumentCollection::class);
        ($objectManager->get(Dispatcher::class))->dispatch(__CLASS__, $dispatchMethod, [
            $pageUid,
            $documentCollection,
        ]);
        $this->writer->saveDocuments($documentCollection, $path);
        foreach ($typo3PageRepository->getPagesByPidAndDoktype($pageUid, [
            PageRepository::DOKTYPE_DEFAULT,
            PageRepository::DOKTYPE_SYSFOLDER,
            PageRepository::DOKTYPE_SHORTCUT,
        ]) as $page) {
            $this->start($page['uid'], $path, $dispatchMethod);
        }
    }
}

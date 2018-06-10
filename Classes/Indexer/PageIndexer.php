<?php

namespace SourceBroker\Hugo\Indexer;

use SourceBroker\Hugo\Domain\Model\Document;
use SourceBroker\Hugo\Domain\Model\DocumentCollection;
use SourceBroker\Hugo\Domain\Repository\Typo3PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class PageIndexer
 *
 * @package SourceBroker\Hugo\Indexer
 */
class PageIndexer extends AbstractIndexer
{
    /**
     * @param int $pageUid
     * @param DocumentCollection $documentCollection
     *
     * @return array
     */
    public function getDocumentsForPage(int $pageUid, DocumentCollection $documentCollection): array
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $typo3PageRepository = $objectManager->get(Typo3PageRepository::class);
        $page = $typo3PageRepository->getByUid($pageUid);
        $layout = $page['backend_layout'];

        $parentPage = null;
        if ($page['pid']) {
            $parentPage = $typo3PageRepository->getByUid($page['pid']);
        }

        if (empty($layout)) {
            /** @var \TYPO3\CMS\Core\Utility\RootlineUtility $rootLineUtility */
            $rootLineUtility = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\RootlineUtility', $pageUid);
            $pages = $rootLineUtility->get();
            $layout = $this->resolveLayoutForPage($pages, $pageUid);
        }
        $document = $documentCollection->create();

        $document->setStoreFilename('_index')
            ->setId($page['uid'])
            ->setType(Document::TYPE_PAGE)
            ->setPid($page['pid'])
            ->setTitle($page['title'])
            ->setSlug($this->slugify($page['nav_title'] ?: $page['title']))
            ->setDraft(!empty($page['hidden']))
            ->setWeight($page['sorting'])
            ->setLayout(str_replace('pagets__', '', $layout))
            ->setContent($typo3PageRepository->getPageContentElements($pageUid));

        if (empty($page['tx_hugo_menuid'])) {
            $menuUid = empty($parentPage['tx_hugo_menuid']) ? '' : $parentPage['tx_hugo_menuid'];
        } else {
            $menuUid = $page['tx_hugo_menuid'];
        }
        $document->addToMenu($menuUid, $page, $parentPage);

        return [
            $pageUid,
            $documentCollection,
        ];
    }

    /**
     * @param array $tree
     * @param int $pageUid
     * @return string
     */
    private function resolveLayoutForPage(array $tree, int $pageUid)
    {
        krsort($tree);

        foreach ($tree as $key => $page) {

            if ($pageUid == $page['uid'] && !empty($page['backend_layout'])) {
                return $page['backend_layout'];
            }

            if(!empty($page['backend_layout_next_level'])) {
                return $page['backend_layout_next_level'];
            }
        }

        return '';
    }
}
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
     * @param Document $document
     *
     * @return array
     */
    public function run(int $pageUid, Document $document): array
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $typo3PageRepository = $objectManager->get(Typo3PageRepository::class);
        $page = $typo3PageRepository->getByUid($pageUid);
        if ($page['pid']) {
            $parentPage = $typo3PageRepository->getByUid($page['pid']);
        }

        $document->setId($page['uid'])
            ->setTitle($page['title'])
            ->setSlug($this->slugify($page['nav_title'] ?: $page['title']))
            ->setDraft(!empty($page['hidden']))
            ->setDeleted(!empty($page['deleted']))
            ->setLayout(strtolower(str_replace('pagets__', '', empty($page['backend_layout']) ? $parentPage['backend_layout'] : $page['backend_layout'])))
            ->setRoot(!empty($page['is_siteroot']))
            ->setContent($typo3PageRepository->getPageContentElements($pageUid));

        if (empty($page['tx_hugo_menuid'])) {
            $menuUid = empty($parentPage['tx_hugo_menuid']) ? '' : $parentPage['tx_hugo_menuid'];
        } else {
            $menuUid = $page['tx_hugo_menuid'];
        }
        $document->addToMenu($menuUid, $page);

        return [
            $pageUid,
            $document,
        ];
    }

    public function runCollection(int $pageUid, DocumentCollection $documentCollection): array
    {

    }

}

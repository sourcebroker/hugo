<?php

namespace SourceBroker\Hugo\Indexer;

use SourceBroker\Hugo\Configuration\Configurator;
use SourceBroker\Hugo\Domain\Model\DocumentCollection;
use SourceBroker\Hugo\Domain\Repository\Typo3PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Page\PageRepository;

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
        $rootline = ($objectManager->get(\TYPO3\CMS\Core\Utility\RootlineUtility::class, $pageUid))->get();
        $layout = $page['backend_layout'] ? $page['backend_layout'] : $this->resolveLayoutForPage($rootline, $pageUid);

        if (!in_array($page['doktype'], [
                PageRepository::DOKTYPE_SYSFOLDER,
                PageRepository::DOKTYPE_SHORTCUT
            ]
        )) {
            $document = $documentCollection->create();
            $document->setStoreFilename('_index')
                ->setId($page['uid'])
                ->setPid($page['pid'])
                ->setTitle($page['title'])
                ->setSlug($this->slugify($page['nav_title'] ?: $page['title']))
                ->setDraft(!empty($page['hidden']))
                ->setWeight($page['sorting'])
                ->setLayout(str_replace('pagets__', '', $layout))
                ->setContent($typo3PageRepository->getPageContentElements($pageUid))
                ->setMenu($page)
                ->setCustomFields($this->resolveCustomeFields($page));
        }
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

            if (!empty($page['backend_layout_next_level'])) {
                return $page['backend_layout_next_level'];
            }
        }

        return '';
    }

    private function resolveCustomeFields(array $page) {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $config = $objectManager->get(Configurator::class, null, $page['uid']);
        $customFields = [];
        foreach ($config->getOption('page.indexer.customFields.fieldMapper') as $fieldToMap => $fieldOptions) {
            $type = empty($fieldOptions['type']) ? null : $fieldOptions['type'];
            switch ($type) {
                default:
                    $customFields[$fieldOptions['name']] = $page[$fieldToMap];
            }
        }
        return $customFields;
    }
}
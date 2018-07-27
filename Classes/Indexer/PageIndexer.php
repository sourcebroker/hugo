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
    /** @var Typo3PageRepository */
    private $typo3PageRepository;

    /**
     * @param int $pageUid
     * @param DocumentCollection $documentCollection
     *
     * @return array
     */
    public function getDocumentsForPage(int $pageUid, DocumentCollection $documentCollection): array
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $hugoConfig = Configurator::getByPid($pageUid);
        $this->typo3PageRepository = $objectManager->get(Typo3PageRepository::class);
        $page = $this->typo3PageRepository->getByUid($pageUid);
        $rootline = ($objectManager->get(\TYPO3\CMS\Core\Utility\RootlineUtility::class, $pageUid))->get();
        $layout = $page['backend_layout'] ? $page['backend_layout'] : $this->resolveLayoutForPage($rootline, $pageUid);

        switch ($hugoConfig->getOption('page.indexer.layout.nameTransform')) {
            default:
                $layout = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $layout));
        }

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
                ->setDraft(!empty($page['hidden']))
                ->setWeight($page['sorting'])
                ->setLayout(str_replace('pagets__', '', $layout))
                ->setContent($this->typo3PageRepository->getPageContentElements($pageUid))
                ->setMenu($page)
                ->setCustomFields($this->resolveCustomFields($page));

            $languages = $hugoConfig->getOption('languages');
            $translations = $this->typo3PageRepository->getPageTranslations($page['uid']);
            if (!empty($translations)) {
                foreach ($translations as $translation) {
                    $document = $documentCollection->create();
                    $document->setStoreFilename('_index.' . $languages[$translation['sys_language_uid']])
                        ->setId($page['uid'])
                        ->setPid($page['pid'])
                        ->setTitle($translation['title'])
                        ->setDraft(!empty($page['hidden']))
                        ->setWeight($page['sorting'])
                        ->setLayout(str_replace('pagets__', '', $layout))
                        ->setContent($this->typo3PageRepository->getPageContentElements(
                            $pageUid,
                            (int)$translation['sys_language_uid'])
                        )
                        ->setMenu($page, $translation)
                        ->setCustomFields($this->resolveCustomFields($page));
                    if (!$page['is_siteroot']) {
                        $document->setCustomFields([
                            'url' => $languages[$translation['sys_language_uid']] . '/' . $this->resolveFullLangPath($rootline,
                                    $translation['sys_language_uid'])
                        ]);
                    }
                }
            }
        }
        return [
            $pageUid,
            $documentCollection,
        ];
    }

    /**
     * @param array $rootline
     * @param int $sysLangugeUid
     * @return string
     */
    private function resolveFullLangPath(array $rootline, int $sysLangugeUid): string
    {
        array_pop($rootline);
        $rootline = array_reverse($rootline);
        $pathParts = [];
        foreach ($rootline as $key => $page) {
            if (!in_array($page['doktype'], [
                    PageRepository::DOKTYPE_SYSFOLDER,
                    PageRepository::DOKTYPE_SHORTCUT
                ]
            )) {
                $translation = $this->typo3PageRepository->getPageTranslation($page['uid'], $sysLangugeUid);
                if (!empty($translation[0]['title'])) {
                    $pathParts[] = $this->slugify(!empty($translation[0]['nav_title']) ? $translation[0]['nav_title'] : $translation[0]['title']);
                }
            }
        }
        return implode('/', $pathParts);
    }

    /**
     * @param array $tree
     * @param int $pageUid
     * @return string
     */
    private function resolveLayoutForPage(array $tree, int $pageUid): string
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

    /**
     * @param array $page
     * @return array
     */
    private function resolveCustomFields(array $page): array
    {
        $config = Configurator::getByPid($page['uid']);
        $customFields = [];
        foreach ((array)$config->getOption('page.indexer.customFields.fieldMapper') as $fieldToMap => $fieldOptions) {
            $type = empty($fieldOptions['type']) ? null : $fieldOptions['type'];
            switch ($type) {
                default:
                    $customFields[$fieldOptions['name']] = $page[$fieldToMap];
            }
        }
        return $customFields;
    }
}
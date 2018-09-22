<?php

namespace SourceBroker\Hugo\Indexer;

use SourceBroker\Hugo\Configuration\Configurator;
use SourceBroker\Hugo\Domain\Model\DocumentCollection;
use SourceBroker\Hugo\Domain\Repository\Typo3PageRepository;
use SourceBroker\Hugo\Service\BackendLayoutService;
use SourceBroker\Hugo\Utility\RootlineUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class PageIndexer
 *
 */
class PageIndexer extends AbstractIndexer
{
    /** @var Typo3PageRepository */
    private $typo3PageRepository;

    /**
     * @var array
     */
    private static $contentElementStorage = [];

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
        $layout = ($objectManager->get(BackendLayoutService::class))->getIdentifierByPage($pageUid);
        $layout = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $layout));

        if (!in_array($page['doktype'], [
                PageRepository::DOKTYPE_SYSFOLDER,
                PageRepository::DOKTYPE_SHORTCUT
            ]
        )) {
            $contentElements = $this->getPageContentElements($pageUid);
            $this->applyContentSlide($contentElements, $pageUid);

            $document = $documentCollection->create();
            $document->setStoreFilename('_index')
                ->setId($page['uid'])
                ->setPid($page['pid'])
                ->setTitle($page['title'])
                ->setDraft(!empty($page['hidden']))
                ->setWeight($page['sorting'])
                ->setLayout(str_replace('pagets__', '', $layout))
                ->setContent($contentElements)
                ->setMenu($page)
                ->setCustomFields($this->resolveCustomFields($page));

            $languages = $hugoConfig->getOption('languages');
            $translations = $this->typo3PageRepository->getPageTranslations($page['uid']);

            foreach ($translations as $translation) {
                $translationContentElements = $this->getPageContentElements(
                    $pageUid,
                    (int)$translation['sys_language_uid']
                );
                $this->applyContentSlide($translationContentElements, $pageUid, (int)$translation['sys_language_uid']);

                $document = $documentCollection->create();
                $document->setStoreFilename('_index.' . $languages[$translation['sys_language_uid']])
                    ->setId($page['uid'])
                    ->setPid($page['pid'])
                    ->setTitle($translation['title'])
                    ->setDraft(!empty($page['hidden']))
                    ->setWeight($page['sorting'])
                    ->setLayout(str_replace('pagets__', '', $layout))
                    ->setContent($translationContentElements)
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
        return [
            $pageUid,
            $documentCollection,
        ];
    }

    /**
     * @param int $pageUid
     * @param int $sysLanguageUid
     *
     * @return array
     */
    protected function getPageContentElements(int $pageUid, int $sysLanguageUid = 0)
    {
        if (empty(self::$contentElementStorage[$pageUid][$sysLanguageUid])) {
            self::$contentElementStorage[$pageUid][$sysLanguageUid] =
                $this->typo3PageRepository->getPageContentElements($pageUid, $sysLanguageUid);
        }

        return self::$contentElementStorage[$pageUid][$sysLanguageUid];
    }

    /**
     * @param array $contentElements
     * @param int $pageUid
     * @param int $sysLanguageUid
     *
     * @return void
     */
    protected function applyContentSlide(array &$contentElements, int $pageUid, int $sysLanguageUid = 0): void
    {
        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageUid)->get();

        if (count($rootLine) < 2) {
            return;
        }

        $backendLayoutService = GeneralUtility::makeInstance(ObjectManager::class)->get(BackendLayoutService::class);
        $colPoses = $backendLayoutService->getColPosesByPage($pageUid);
        $depthLevel = 0;
        array_shift($rootLine);

        foreach ($rootLine as $parentPage) {
            $colPosesToFill = array_filter(
                $backendLayoutService->getColPosesByPageAndSlideLevel($pageUid, ++$depthLevel),
                function ($colPos) use ($contentElements) {
                    return empty($this->filterContentElementsByColPoses($contentElements, [$colPos]));
                }
            );

            $parentContentElements = $this->getPageContentElements($parentPage['uid'], $sysLanguageUid);

            // By default all colPoses that does not exist in current page backend layout are treated as sliding with -1.
            // It means we need to merge $colPosesToFill with the colPos that: (1) Are not filled yet AND (2) does not exist in current page backend layout
            $colPosFilledInParentButStillEmpty = array_diff(
                array_column($parentContentElements, 'colPos'),
                array_column($contentElements, 'colPos'),
                $colPoses
            );

            $colPosesToFill = array_merge($colPosesToFill, $colPosFilledInParentButStillEmpty);

            $contentElements = array_merge(
                $contentElements,
                $this->filterContentElementsByColPoses($parentContentElements, $colPosesToFill)
            );
        }
    }

    /**
     * @param array $contentElements
     * @param array $colPoses
     *
     * @return array
     */
    private function filterContentElementsByColPoses(array $contentElements, array $colPoses): array
    {
        $colPoses = array_map('intval', $colPoses);

        return array_filter(
            $contentElements,
            function ($contentElement) use ($colPoses) {
                return in_array((int)$contentElement['colPos'], $colPoses, true);
            }
        );
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
     * @param array $page
     * @return array
     */
    private function resolveCustomFields(array $page): array
    {
        $config = Configurator::getByPid($page['uid']);
        $customFields = [];
        foreach ((array)$config->getOption('page.indexer.customFields.fieldMapper') as $fieldToMap => $fieldOptions) {
            $customFields[$fieldOptions['name']] = $page[$fieldToMap];
        }
        return $customFields;
    }
}

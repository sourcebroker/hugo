<?php

namespace SourceBroker\Hugo\Domain\Model;

use SourceBroker\Hugo\Configuration\Configurator;
use SourceBroker\Hugo\Domain\Repository\Typo3PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class Document
 *
 */
class Document
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $pid;

    /**
     * @var string
     */
    protected $slug = '';

    /**
     * @var int
     */
    protected $weight = 0;

    /**
     * @var string
     */
    protected $layout = null;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $frontMatter = [
        'title' => '',
        'draft' => 0,
        'menu' => []
    ];

    /**
     * @var null
     */
    protected $storeFilename = null;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return self
     */
    public function setId(int $id): self
    {
        $this->frontMatter['id'] = $id;
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @param int $pid
     * @return Document
     */
    public function setPid(int $pid): self
    {
        $this->frontMatter['pid'] = $pid;
        $this->pid = $pid;
        return $this;
    }

    /**
     * @return null
     */
    public function getStoreFilename()
    {
        return $this->storeFilename;
    }

    /**
     * @param null $storeFilename
     * @return Document
     */
    public function setStoreFilename($storeFilename): self
    {
        $this->storeFilename = $storeFilename;
        return $this;
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param int $weight
     * @return Document
     */
    public function setWeight(int $weight): self
    {
        $this->frontMatter['weight'] = $weight;
        $this->weight = $weight;
        return $this;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     *
     * @return self
     */
    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @param string $title
     * @return Document
     */
    public function setTitle(string $title): self
    {
        $this->frontMatter['title'] = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->frontMatter['title'];
    }

    /**
     * @return array
     */
    public function getFrontMatter(): array
    {
        return $this->frontMatter;
    }

    /**
     * @return bool
     */
    public function getDraft(): bool
    {
        return (bool)$this->frontMatter['draft'];
    }

    /**
     * @param bool $draft
     * @return Document
     */
    public function setDraft(bool $draft): self
    {
        $this->frontMatter['draft'] = $draft;
        return $this;
    }

    /**
     * @return string
     */
    public function getLayout(): string
    {
        return $this->layout;
    }

    /**
     * @param string $layout
     * @return Document
     */
    public function setLayout(string $layout): self
    {
        $this->frontMatter['layout'] = $layout;
        return $this;
    }

    /**
     * @param $customFields
     * @return Document
     */
    public function setCustomFields($customFields): self
    {
        $this->frontMatter = array_replace_recursive($this->frontMatter, $customFields);
        return $this;
    }

    /**
     * @param array $contentElements
     * @return Document
     */
    public function setContent(array $contentElements): self
    {
        foreach ((array)$contentElements as $contentElement) {
            $this->frontMatter['columns']['col' . $contentElement['colPos']][$contentElement['sorting']] = $contentElement['uid'];
        }
        foreach ((array)$this->frontMatter['columns'] as $key => $values) {
            $this->frontMatter['columns'][$key] = array_values($values);
        }
        return $this;
    }

    /**
     * @param $contentRaw
     * @return Document
     */
    public function setContentRaw($contentRaw): self
    {
        $this->frontMatter['columns'] = $contentRaw;
        return $this;
    }

    /**
     * @return array
     */
    public function getContent(): array
    {
        return $this->frontMatter['columns'] ?? [];
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Document
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param $page
     * @param null $pageTranslation
     * @return Document
     */
    public function setMenu($page, $pageTranslation = null): self
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $hugoConfig = Configurator::getByPid((int)$page['uid']);
        if (!empty($hugoConfig->getOption('page.indexer.menu')) && is_array($hugoConfig->getOption('page.indexer.menu'))) {
            foreach ($hugoConfig->getOption('page.indexer.menu') as $menuIdentifier => $menuConfig) {
                $typo3PageRepository = $objectManager->get(Typo3PageRepository::class);
                $shortcuts = $typo3PageRepository->getShortcutsPointingToPage($page['uid']);
                $checkPages = array_merge($shortcuts, [$page]);
                foreach ($checkPages as $checkPage) {
                    $rootline = ($objectManager->get(RootlineUtility::class, $checkPage['uid']))->get();
                    krsort($rootline);
                    foreach ($rootline as $key => $rootlinePage) {
                        $pageBelongsToMenuAndIsNotBelowSysfolder = true;
                        $pageBelongsToMenuAndIsNotBelowHiddenInNavigation = true;
                        $doktypeInRootline = false;
                        foreach ($rootline as $rootlinePageBelongs) {
                            if ($doktypeInRootline === true && $rootlinePageBelongs['uid'] === $menuConfig['entryUid']) {
                                $pageBelongsToMenuAndIsNotBelowSysfolder = false;
                            }
                            if ($rootlinePageBelongs['doktype'] === PageRepository::DOKTYPE_SYSFOLDER) {
                                $doktypeInRootline = true;
                            }
                            if ($rootlinePageBelongs['nav_hide'] && empty($menuConfig['showHiddenInMenu'])) {
                                $pageBelongsToMenuAndIsNotBelowHiddenInNavigation = false;
                            }
                        }
                        if (empty($page['nav_hide']) &&
                            $page['uid'] !== (int)$menuConfig['entryUid'] &&
                            $rootlinePage['uid'] === (int)$menuConfig['entryUid']
                            && $pageBelongsToMenuAndIsNotBelowSysfolder
                            && $pageBelongsToMenuAndIsNotBelowHiddenInNavigation) {
                            if (!empty($pageTranslation['sys_language_uid'])) {
                                $rootlinePageTranslation = $typo3PageRepository->getPageTranslation($checkPage['uid'],
                                    $pageTranslation['sys_language_uid']);
                                $title = $rootlinePageTranslation[0]['title'];
                            } else {
                                $title = $checkPage['title'];
                            }

                            $menu = [
                                'weight' => $checkPage['sorting'],
                                'identifier' => $checkPage['uid'],
                                'name' => $title,
                            ];
                            if ($title !== $page['title']) {
                                $menu = array_merge($menu, ['name' => $title]);
                            }
                            if ($checkPage['pid'] && $checkPage['pid'] !== (int)$menuConfig['entryUid']) {
                                $menu = array_merge($menu, ['parent' => $checkPage['pid']]);
                            }
                            $this->frontMatter['menu'][$menuIdentifier] = $menu;
                        }
                    }
                }
            }
        }
        return $this;
    }
}

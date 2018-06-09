<?php

namespace SourceBroker\Hugo\Domain\Model;

/**
 * Class Document
 *
 * @package SourceBroker\Hugo\Traversing
 */
class Document
{
    const TYPE_NODE = 0;
    const TYPE_PAGE = 10;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $pid;

    /**
     * @var array
     */
    protected $path;

    /**
     * @var string
     */
    protected $slug = '';

    /**
     * @var int
     */
    protected $type = self::TYPE_NODE;

    /**
     * @var bool
     */
    protected $deleted = false;

    /**
     * @var bool
     */
    protected $root = false;

    /**
     * @var int
     */
    protected $weight = 0;

    /**
     * @var string
     */
    protected $layout = null;

    /**
     * @var array
     */
    protected $frontMatter = [
        'title' => '',
        'draft' => 0,
        'menu' => []
    ];

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
     * @return array
     */
    public function getPath(): array
    {
        return $this->path;
    }

    /**
     * @param array $path
     *
     * @return self
     */
    public function setPath(array $path): self
    {
        $this->path = $path;
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
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     *
     * @return self
     */
    public function setType(int $type): self
    {
        $this->type = $type;
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
    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     *
     * @return self
     */
    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;
        return $this;
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
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->root;
    }

    /**
     * @param bool $root
     * @return Document
     */
    public function setRoot(bool $root): self
    {
        $this->root = $root;
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
     * @param string $menuId
     * @param $page
     * @param $parentPage
     * @return Document
     */
    public function addToMenu(string $menuId, $page, $parentPage = null): self
    {
        if (empty($page['nav_hide'])) {
            if (!in_array($menuId, $this->frontMatter['menu']) && !$page['is_siteroot']) {
                $menu = [
                    'weight' => $page['sorting'],
                    'identifier' => $page['uid']
                ];
                if (empty($parentPage['is_siteroot']) && $page['pid']) {
                    $menu = array_merge($menu, ['parent' => $page['pid']]);
                }
                $this->frontMatter['menu'][$menuId] = $menu;
            }
        }
        return $this;
    }
}
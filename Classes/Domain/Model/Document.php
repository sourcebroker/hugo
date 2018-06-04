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
        $this->id = $id;
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
     * @return Document
     */
    public function addToMenu(string $menuId, $row): self
    {
        if(empty($row['nav_hide'])) {
            if (!in_array($menuId, $this->frontMatter['menu'])) {
                $this->frontMatter['menu'][$menuId] = [
                    'weight' => $row['sorting'],
                    'identifier' => $row['uid']
                ];
            }
        }
        return $this;
    }


}
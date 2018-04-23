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
     * @var array
     */
    protected $metaData = [
        'title' => '',
        'draft' => 0,
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
        $this->metaData['title'] = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->metaData['title'];
    }

    /**
     * @return array
     */
    public function getMetaData(): array
    {
        return $this->metaData;
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

    public function getDraft(): bool
    {
        return (bool)$this->metaData['draft'];
    }

    /**
     * @param bool $draft
     * @return Document
     */
    public function setDraft(bool $draft): self
    {
        $this->metaData['draft'] = $draft;
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

}
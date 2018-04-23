<?php

namespace SourceBroker\Hugo\Writer;

use SourceBroker\Hugo\Domain\Model\Document;
use SourceBroker\Hugo\Domain\Model\DocumentCollection;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class YamlWriter
 * @package SourceBroker\Hugo\Writer
 */
class YamlWriter implements WriterInterface
{
    /**
     * @var string
     */
    protected $ext = 'yaml';

    /**
     * @var string
     */
    protected $rootPath;

    /**
     * @param string $path
     */
    public function setRootPath(string $path): void
    {
        $this->rootPath = rtrim($path, DIRECTORY_SEPARATOR) . '/';
    }


    /**
     * @param Document $document
     * @param array $path
     */
    public function save(Document $document, array $path): void
    {
        switch ($document->getType()) {
            case Document::TYPE_PAGE:
                $documentName = '_index';
                break;
            default:
                if (empty($document->getId())) {
                    throw new \RuntimeException('Id of document is misisng', 1693179681746);
                }

                $documentName = $document->getId() . '_' . ucfirst($document->getSlug());
        }

        $filename = $documentName . '.' . $this->ext;

        $fullPath = GeneralUtility::getFileAbsFileName($this->rootPath . implode('/', $path)) . '/' . $filename;

        $content = Yaml::dump($document->getMetaData());

        GeneralUtility::mkdir_deep(dirname($fullPath));

        file_put_contents($fullPath, $content);
    }

    /**
     * @param DocumentCollection $collection
     * @param array $path
     */
    public function saveDocuments(DocumentCollection $collection, array $path): void
    {
        foreach ($collection as $document) {
            $this->save($document, $path);
        }
    }

    /**
     * clean root path folder
     */
    public function clean(): void
    {
        GeneralUtility::rmdir($this->rootPath, true);
    }
}
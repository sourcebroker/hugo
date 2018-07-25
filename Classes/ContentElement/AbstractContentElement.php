<?php

namespace SourceBroker\Hugo\ContentElement;

use SourceBroker\Hugo\Configuration\Configurator;
use SourceBroker\Hugo\Indexer\FieldTransformer;
use SourceBroker\Hugo\Service\RteService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class AbstractContentElement
 * @package SourceBroker\Hugo\ContentElement
 */
abstract class AbstractContentElement implements ContentElementInterface
{
    /**
     * @var RteService
     */
    protected $rteService;

    /**
     * @var Configurator
     */
    protected $configurator;

    /**
     * AbstractContentElement constructor.
     *
     * @param Configurator $configurator
     */
    public function __construct(Configurator $configurator)
    {
        $this->configurator = $configurator;
    }

    /**
     * @param array $contentElementRawData
     * @return array
     */
    public function getCommonContentElementData(array $contentElementRawData): array
    {
        $content = [];

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var \SourceBroker\Hugo\Configuration\Configurator $config */
        $config = $objectManager->get(Configurator::class, null, $contentElementRawData['pid']);
        $fieldTransformer = $objectManager->get(FieldTransformer::class);
        foreach ((array)$config->getOption('content.indexer.commonFields.fieldMapper') as $fieldToMap => $fieldOptions) {
            $fields = [];
            foreach (GeneralUtility::trimExplode(',', $fieldOptions['from']) as &$fieldName) {
                if (!empty($contentElementRawData[$fieldName])) {
                    $fields[] = $contentElementRawData[$fieldName];
                }
            }
            $content[$fieldToMap] = implode(',', $fields);

            if (!empty($content[$fieldToMap])) {
                if (isset($fieldOptions['transforms']) && is_array($fieldOptions['transforms'])) {
                    foreach ($fieldOptions['transforms'] as $transform) {
                        if (method_exists($fieldTransformer, $transform['type'])) {
                            $content[$fieldToMap] = $fieldTransformer->{$transform['type']}(
                                $content[$fieldToMap], 
                                $contentElementRawData,
                                $fieldOptions['from'],
                                !empty($transform['method']) ? $transform['method'] : null
                            );
                        }
                    }
                }
            }
        }
        return $content;
    }

    /**
     * @param array $contentElementRawData
     * @return array
     */
    public function getSpecificContentElementData(array $contentElementRawData): array
    {

    }

    /**
     * @param $contentElementRawData
     * @return array
     */
    public function getData($contentElementRawData): array
    {
        return array_replace_recursive(
            $this->getCommonContentElementData($contentElementRawData),
            $this->getSpecificContentElementData($contentElementRawData)
        );
    }

    /**
     * @return RteService
     */
    protected function getRteService()
    {
        if (!$this->rteService instanceof RteService) {
            $this->rteService = GeneralUtility::makeInstance(RteService::class);
        }

        return $this->rteService;
    }
}
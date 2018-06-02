<?php

namespace SourceBroker\Hugo\ContentElement;

/**
 * Class AbstractContentElement
 * @package SourceBroker\Hugo\ContentElement
 */
abstract class AbstractContentElement implements ContentElementInterface
{
    /**
     * @param array $contentElementRawData
     * @return array
     */
    public function getCommonContentElementData(array $contentElementRawData): array
    {
        $content = [
            'type' => $contentElementRawData['CType'],
            'draft' => $contentElementRawData['hidden'],
            'publishDate' => $contentElementRawData['starttime'] ? date('Y-m-d\TH:i:s\Z', $contentElementRawData['starttime']) : '',
            'expireDate' => $contentElementRawData['endtime'] ? date('Y-m-d\TH:i:s\Z', $contentElementRawData['endtime']) : '',
            'sectionClasses' => implode(' ', array_filter(array_merge(
                // TODO: make it configurable to allow to add own fields
                explode(',', $contentElementRawData['space_before_class']),
                explode(',', $contentElementRawData['space_after_class'])
            ))),
            'sectionTitle' => $contentElementRawData['header'],
            'sectionTitleSub' => $contentElementRawData['subheader'],
            'sectionTitleLayout' => (int)$contentElementRawData['header_layout'],
        ];
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
}
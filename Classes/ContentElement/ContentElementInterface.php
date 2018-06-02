<?php

namespace SourceBroker\Hugo\ContentElement;

interface ContentElementInterface
{
    /**
     * @param array $contentElementRawData
     * @return array
     */
    public function getCommonContentElementData(array $contentElementRawData): array;

    /**
     * @param array $contentElementRawData
     * @return array
     */
    public function getSpecificContentElementData(array $contentElementRawData): array;
}
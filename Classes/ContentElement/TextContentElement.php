<?php

namespace SourceBroker\Hugo\ContentElement;

class TextContentElement extends AbstractContentElement
{
    /**
     * @param array $contentElementRawData
     * @return array
     */
    public function getSpecificContentElementData(array $contentElementRawData): array
    {
        return [
            'text' => $this->getRteService()->parse($contentElementRawData['bodytext'], $this->configurator),
        ];
    }
}
<?php

namespace SourceBroker\Hugo\ContentElement;

class FallbackContentElement extends AbstractContentElement
{
    /**
     * @param array $contentElementRawData
     * @return array
     */
    public function getSpecificContentElementData(array $contentElementRawData): array
    {
        return [
            'type' => 'warning',
            'warningNote' => 'No exporter for content element with CType: ' . $contentElementRawData['CType']
        ];
    }
}
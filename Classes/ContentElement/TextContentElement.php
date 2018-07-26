<?php

namespace SourceBroker\Hugo\ContentElement;

use SourceBroker\Hugo\Configuration\Configurator;

class TextContentElement extends AbstractContentElement
{
    /**
     * @param array $contentElementRawData
     * @return array
     */
    public function getSpecificContentElementData(array $contentElementRawData): array
    {
        return [
            'text' => $this->getRteService()->parse(
                $contentElementRawData['bodytext'],
                Configurator::getByPid((int)$contentElementRawData['pid'])
            ),
        ];
    }
}
<?php

namespace SourceBroker\Hugo\ContentElement;

class GridelementsPi1ContentElement extends AbstractContentElement
{
    /**
     * @param array $contentElementRawData
     * @return array
     */
    public function getSpecificContentElementData(array $contentElementRawData): array
    {

        /*
         *
         * Expected example content for 2 column:
         *
         */

        return [
            'type' => 'grid2col',
            'columns' => [
                'col1' => [
                    'classes' => '',
                    'contentElements' => [1, 2, 3],
                ],
                'col2' => [
                    'classes' => '',
                    'contentElements' => [2, 3, 4],
                ],
            ]
        ];
    }


}
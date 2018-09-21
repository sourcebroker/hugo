<?php

namespace SourceBroker\Hugo\Indexer;

/**
 * Class FieldTransformer
 *
 * @package SourceBroker\Hugo\Indexer
 */
class FieldTransformer
{
    /**
     * Convert timestamp to another date format
     *
     * @param string $currentValue
     * @return string
     */
    public function convertDate($currentValue)
    {
        return $currentValue ? date('Y-m-d\TH:i:s\Z', $currentValue) : '';
    }

    /**
     * Add space classes
     *
     * @param string $currentValue
     * @param array $contentElementRawData
     * @return string
     */
    public function addSpaceClass($currentValue, $contentElementRawData)
    {
        return implode(' ', array_filter(array_merge(
            explode(',', $contentElementRawData['space_before_class']),
            explode(',', $contentElementRawData['space_after_class'])
        )));
    }

    /**
     * Replace commas with spaces in the string
     *
     * @param string $currentValue
     * @param array $contentElementRawData
     * @param string $fieldName
     * @param string $method
     * @return string
     */
    public function commaToSpace(
        $currentValue,
        /** @noinspection PhpUnusedParameterInspection */
        $contentElementRawData,
        /** @noinspection PhpUnusedParameterInspection */
        $fieldName,
        $method
    ) {
        $fieldElements = preg_split('/,[\s]*/', $currentValue);

        if ($method == 'merge') {
            return implode(' ', array_merge([$currentValue], $fieldElements));
        } else {
            return implode(' ', $fieldElements);
        }
    }

    /**
     * Remove duplicate classes from string
     *
     * @param string $currentValue
     * @return string
     */
    public function removeDuplicateClasses($currentValue)
    {
        $classes = explode(' ', $currentValue);

        return trim(implode(' ', array_unique($classes)), ' ');
    }

    /**
     * Convert string to integer
     *
     * @param string $currentValue
     * @return integer
     */
    public function toInteger($currentValue)
    {
        return (int)$currentValue;
    }
}
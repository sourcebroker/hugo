<?php

namespace SourceBroker\Hugo\ContentElement;

class DceContentElement extends AbstractContentElement
{
    /**
     * @param array $contentElementRawData
     * @return array
     */
    public function getSpecificContentElementData(array $contentElementRawData): array
    {
        /* @var $dce \ArminVieweg\Dce\Domain\Model\Dce */
        $dce = \ArminVieweg\Dce\Utility\DatabaseUtility::getDceObjectForContentElement($contentElementRawData['uid']);
        $fields = [];
        /** @var $field \ArminVieweg\Dce\Domain\Model\DceField */
        foreach ($dce->getFields() as $field) {
            if ($field->isTab()) {
                continue;
            }
            if ($field->hasSectionFields()) {
                /** @var $sectionField \ArminVieweg\Dce\Domain\Model\DceField */
                foreach ($field->getSectionFields() as $sectionField) {
                    $sectionFieldValues = $sectionField->getValue();
                    if (is_array($sectionFieldValues)) {
                        foreach ($sectionFieldValues as $i => $value) {
                            if (!empty($value[0]) && is_object($value[0]) && get_class($value[0]) == \TYPO3\CMS\Core\Resource\File::class) {
                                // TODO: Make support for more that one image.
                                /* @var $file \TYPO3\CMS\Core\Resource\File */
                                $file = $value[0];
                                $fields[$field->getVariable()][$i][$sectionField->getVariable()] = $file->getProperty('uid');
                            } elseif ($this->fieldIsLink($sectionField)) {
                                // TODO: Parse for links. Hugo must have final links.
                                $fields[$field->getVariable()][$i][$sectionField->getVariable()] = $value;
                            } else {
                                $fields[$field->getVariable()][$i][$sectionField->getVariable()] = $value;
                            }
                        }
                    }
                }
            } else {
                if (!empty($value[0]) && is_object($value[0]) && get_class($value[0]) == \TYPO3\CMS\Core\Resource\File::class) {
                    // TODO: Make support for more that one image.
                    $fields[$field->getVariable()] = $value[0]->getProperty('uid');
                } elseif ($this->fieldIsLink($field)) {
                    // TODO: Parse for links. Hugo must have final links.
                    $fields[$field->getVariable()] = $field->getValue();
                } else {
                    $fields[$field->getVariable()] = $field->getValue();
                }
            }
        }
        return $fields;
    }

    /**
     *
     * @param $field
     * @return bool
     */
    protected function fieldIsLink(\ArminVieweg\Dce\Domain\Model\DceField $field): bool
    {
        $isFieldLink = false;
        $configuration = $field->getConfigurationAsArray();
        $configuration = $configuration['config'];
        if ($configuration['type'] === 'input'
            && strpos($configuration['softref'], 'typolink') >= 0
            && !empty($configuration['wizards']['link'])) {
            $isFieldLink = true;
        };
        return $isFieldLink;
    }

}
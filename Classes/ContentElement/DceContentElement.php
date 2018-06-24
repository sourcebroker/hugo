<?php

namespace SourceBroker\Hugo\ContentElement;

use SourceBroker\Hugo\Service\Typo3UrlService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;

class DceContentElement extends AbstractContentElement
{
    /**
     * @param array $contentElementRawData
     *
     * @return array
     */
    public function getSpecificContentElementData(array $contentElementRawData): array
    {
        $languageUid = (int)$contentElementRawData['sys_language_uid'];
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
                                $fields[$field->getVariable()][$i][$sectionField->getVariable()] = $this->getSysFileIds($value);
                            } elseif ($this->fieldIsLink($sectionField)) {
                                $linkArray = $this->convertTypolinkToLinkArray($value, $languageUid);
                                $fields[$field->getVariable()][$i][$sectionField->getVariable()] = $linkArray;
                            } else {
                                $fields[$field->getVariable()][$i][$sectionField->getVariable()] = $value;
                            }
                        }
                    }
                }
            } else {
                $value = $field->getValue();
                if (
                    !empty($value[0]) &&
                    is_object($value[0]) &&
                    in_array(
                        get_class($value[0]),
                        [
                            \TYPO3\CMS\Core\Resource\FileReference::class,
                            \TYPO3\CMS\Core\Resource\File::class
                        ]
                    )
                ) {
                    $fields[$field->getVariable()] = $this->getSysFileIds((array)$value);
                } elseif ($this->fieldIsLink($field)) {
                    $fields[$field->getVariable()] = $this->convertTypolinkToLinkArray($value, $languageUid);
                } else {
                    $fields[$field->getVariable()] = $field->getValue() ;
                }
            }
        }
        $dceRaw = BackendUtility::getRecord('tx_dce_domain_model_dce', $dce->getUid());
        if (!empty($dceRaw['tx_hugo_typename'])) {
            $fields['type'] = $dceRaw['tx_hugo_typename'];
        }

        return $fields;
    }

    /**
     *
     * @param $field
     *
     * @return bool
     */
    protected function fieldIsLink(\ArminVieweg\Dce\Domain\Model\DceField $field): bool
    {
        $isFieldLink = false;
        $configuration = $field->getConfigurationAsArray();
        $configuration = $configuration['config'];
        if ($configuration['type'] === 'input'
            && strpos($configuration['softref'], 'typolink') >= 0
            && (!empty($configuration['wizards']['link']) || !empty($configuration['fieldControl']['linkPopup']))) {
            $isFieldLink = true;
        }

        return $isFieldLink;
    }

    /**
     * @param string $value
     * @param int $languageUid
     *
     * @return array
     */
    protected function convertTypolinkToLinkArray(string $value, int $languageUid): ?array
    {
        return GeneralUtility::makeInstance(ObjectManager::class)->get(Typo3UrlService::class)->linkArray($value,
            $languageUid);
    }

    /**
     * @param array $values
     *
     * @return array
     */
    protected function getSysFileIds($values): array
    {
        $data = [];
        foreach ($values as $object) {
            if ($object instanceof File) {
                $data[] = [
                    'uid' => $object->getProperty('uid'),
                    'title' => $object->getProperty('title') ?: '',
                    'alternative' => $object->getProperty('alternative') ?: '',
                    'description' => $object->getProperty('description') ?: ''
                ];
            } elseif ($object instanceof FileReference) {
                $originalFile = $object->getOriginalFile();
                $data[] = [
                    'uid' => $originalFile->getProperty('uid'),
                    'title' => $object->getTitle() ?: ($originalFile->getProperty('title') ?: ''),
                    'alternative' => $object->getAlternative() ?: ($originalFile->getProperty('alternative') ?: ''),
                    'description' => $object->getDescription() ?: ($originalFile->getProperty('description') ?: ''),
                ];
            }
        }

        return $data;
    }
}

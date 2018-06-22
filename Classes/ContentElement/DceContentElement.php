<?php

namespace SourceBroker\Hugo\ContentElement;

use SourceBroker\Hugo\Service\Typo3UrlService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class DceContentElement extends AbstractContentElement
{
    /**
     * @param array $contentElementRawData
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
                                // TODO: Make support for more that one image.
                                /* @var $file \TYPO3\CMS\Core\Resource\File */
                                $file = $value[0];
                                $fields[$field->getVariable()][$i][$sectionField->getVariable()] = $file->getProperty('uid');
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
        $dceRaw = BackendUtility::getRecord('tx_dce_domain_model_dce', $dce->getUid());
        if (!empty($dceRaw['tx_hugo_typename'])) {
            $fields['type'] = $dceRaw['tx_hugo_typename'];
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

    /**
     * @param string $value
     * @param int   $languageUid
     *
     * @return array
     */
    protected function convertTypolinkToLinkArray(string $value, int $languageUid): array
    {
        return GeneralUtility::makeInstance(ObjectManager::class)->get(Typo3UrlService::class)->linkArray($value, $languageUid);
    }

}
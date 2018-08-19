<?php

namespace SourceBroker\Hugo\ContentElement;

use ArminVieweg\Dce\Domain\Model\Dce;
use ArminVieweg\Dce\Domain\Model\DceField;
use SourceBroker\Hugo\Configuration\Configurator;
use SourceBroker\Hugo\Service\Typo3UrlService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class DceContentElement extends AbstractContentElement
{
    /**
     * @param array $contentElementRawData
     *
     * @return array
     */
    public function getSpecificContentElementData(array $contentElementRawData): array
    {
        /* @var $dce Dce */
        $dce = \ArminVieweg\Dce\Utility\DatabaseUtility::getDceObjectForContentElement($contentElementRawData['uid']);
        $fields = [];
        /** @var $field DceField */
        foreach ($dce->getFields() as $field) {
            if ($field->isTab()) {
                continue;
            }
            if ($field->hasSectionFields()) {
                /** @var $sectionField DceField */
                foreach ($field->getSectionFields() as $sectionField) {
                    $sectionFieldValues = $sectionField->getValue();
                    if (!is_array($sectionFieldValues)) {
                        continue;
                    }
                    foreach ($sectionFieldValues as $i => $value) {
                        $fields[$field->getVariable()][$i][$sectionField->getVariable()] = $this->transformValue(
                            $contentElementRawData,
                            $value,
                            $sectionField
                        );
                    }
                }
            } else {
                $fields[$field->getVariable()] = $this->transformValue(
                    $contentElementRawData,
                    $field->getValue(),
                    $field
                );
            }
        }
        $dceRaw = BackendUtility::getRecord('tx_dce_domain_model_dce', $dce->getUid());
        if (!empty($dceRaw['tx_hugo_typename'])) {
            $fields['type'] = $dceRaw['tx_hugo_typename'];
        }

        return $fields;
    }

    /**
     * @param array $contentElementRawData
     * @param mixed $value
     * @param DceField $field
     *
     * @return array|null|string
     */
    protected function transformValue(array $contentElementRawData, $value, DceField $field)
    {
        $languageUid = (int)$contentElementRawData['sys_language_uid'];

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
            return $this->getSysFileIds((array)$value, $contentElementRawData);
        } elseif ($this->fieldIsLink($field)) {
            return $this->convertTypolinkToLinkArray(
                (int)$contentElementRawData['pid'],
                '',
                $value,
                $languageUid
            );
        }

        return $value;
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
     * @param int $pid
     * @param string $linkText
     * @param string $linkParameters
     * @param int $languageUid
     *
     * @return array
     */
    protected function convertTypolinkToLinkArray(
        int $pid,
        string $linkText,
        string $linkParameters,
        int $languageUid
    ): ?array {
        return GeneralUtility::makeInstance(ObjectManager::class)
            ->get(Typo3UrlService::class)
            ->convertToLinkElement(
                $linkParameters,
                Configurator::getByPid($pid),
                $languageUid,
                $linkText
            );
    }

    /**
     * @param array $values
     *
     * @return array
     */
    protected function getSysFileIds($values, $contentElementRawData): array
    {
        $languageUid = (int)$contentElementRawData['sys_language_uid'];
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
                    'link' => $object->getLink() ? $this->convertTypolinkToLinkArray((int)$contentElementRawData['pid'], $object->getLink(), 0, $languageUid) : [],
                ];
            }
        }

        return $data;
    }
}

<?php

namespace SourceBroker\Hugo\ContentElement;

use SourceBroker\Hugo\Domain\Repository\Typo3ContentRepository;
use SourceBroker\Hugo\Service\ExportContentService;

class ShortcutContentElement extends AbstractContentElement
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager;

    /**
     * @var string
     */
    protected $name = 'tt_content';

    /**
     * @param array $contentElementRawData
     * @return array
     * @throws \TYPO3\CMS\Core\Exception
     */
    public function getSpecificContentElementData(array $contentElementRawData): array
    {
        $data = [
            'type' => 'warning',
            'warningNote' => 'Not supported type in shortcut element'
        ];
        if (strpos($contentElementRawData['records'], $this->name) !== false) {
            $elements = array_filter(explode(',', $contentElementRawData['records']), function ($value) {
                return strpos($value, $this->name) !== false;
            });
            if (count($elements)) {
                $uid = substr($elements[0], strlen($this->name) + 1);
                $contentElement = $this->objectManager->get(Typo3ContentRepository::class)->getByUid($uid);
                $data = $this->objectManager->get(ExportContentService::class)->getYamlForSingle($contentElement);
                $data['id'] = (string)$contentElementRawData['uid'];
            }
        }
        return $data;
    }
}

<?php

namespace SourceBroker\Hugo\ContentElement;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Class GridelementsPi1ContentElement
 */
class GridelementsPi1ContentElement extends AbstractContentElement
{
    const SIGNAL = 'classes_for_gridelement';

    /**
     * @var array
     */
    protected $data = [
        'type' => '',
        'columns' => []
    ];

    /**
     * @param array $contentElementRawData
     * @return array
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function getSpecificContentElementData(array $contentElementRawData): array
    {
        $result = [
            'type' => 'warning',
            'warningNote' => 'Missing gridelements extension'
        ];
        if (class_exists(\GridElementsTeam\Gridelements\Backend\LayoutSetup::class)) {
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            $layoutSetup = $objectManager->get(\GridElementsTeam\Gridelements\Backend\LayoutSetup::class);
            $layoutId = $contentElementRawData['tx_gridelements_backend_layout'];
            $layoutSetup->init($contentElementRawData['pid']);
            $setup = $layoutSetup->getLayoutSetup($layoutId);
            $columns = $setup['config']['colCount'];
            $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
            $this->getPluginFlexFormData($contentElementRawData);

            $this->data['type'] = sprintf('grid%scol', $columns);
            $children = $this->getAllChildren($contentElementRawData['uid']);
            for ($i = 0; $i < $columns; $i++) {
                $this->data['columns']['col' . ($i + 1)] = [
                    'classes' => '',
                    'contentElements' => $this->getChildrenForColumn($children, $i),
                ];
            }

            $data = $signalSlotDispatcher->dispatch(
                __CLASS__,
                self::SIGNAL,
                [
                    'data' => $this->data,
                    'row' => $contentElementRawData
                ]
            );
            $result = $data['data'];
        }
        return $result;
    }

    /**
     * @param array $children
     * @param int $column
     * @return array
     */
    protected function getChildrenForColumn(array $children, int $column): array
    {
        $items = [];
        if (isset($children[$column])) {
            foreach ($children[$column] as $child) {
                $items[] = $child['uid'];
            }
        }
        return $items;
    }

    /**
     * @param int $uid
     * @return array
     */
    protected function getAllChildren(int $uid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');

        $items = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where('tx_gridelements_container = :uid')
            ->orderBy('sorting')
            ->setParameter('uid', $uid)
            ->execute()
            ->fetchAll();

        $childrenByColumns = [];
        foreach ($items as $item) {
            $childrenByColumns[$item['tx_gridelements_columns']][] = $item;
        }
        ksort($childrenByColumns);
        return $childrenByColumns;
    }

    /**
     * fetches values from the grid flexform and assigns them to virtual fields in the data array
     * @param array $contentElementRawData
     */
    protected function getPluginFlexFormData(array &$contentElementRawData)
    {
        $pluginFlexForm = GeneralUtility::xml2array($contentElementRawData['pi_flexform']);
        if (is_array($pluginFlexForm) && is_array($pluginFlexForm['data'])) {
            foreach ($pluginFlexForm['data'] as $sheet => $data) {
                if (is_array($data)) {
                    foreach ((array)$data as $language => $value) {
                        if (is_array($value)) {
                            foreach ((array)$value as $key => $val) {
                                $contentElementRawData['flexform_' . $key] = $this->getFlexFormValue($pluginFlexForm, $key, $sheet);
                            }
                        }
                    }
                }
            }
        }
        unset($pluginFlexForm);
    }

    /**
     * Return value from somewhere inside a FlexForm structure
     *
     * @param array $T3FlexForm_array FlexForm data
     * @param string $fieldName Field name to extract. Can be given like "test/el/2/test/el/field_templateObject" where each part will dig a level deeper in the FlexForm data.
     * @param string $sheet Sheet pointer, eg. "sDEF"
     * @param string $language Language pointer, eg. "lDEF"
     * @param string $value Value pointer, eg. "vDEF"
     *
     * @return string The content.
     */
    protected function getFlexFormValue(
        $T3FlexForm_array,
        $fieldName,
        $sheet = 'sDEF',
        $language = 'lDEF',
        $value = 'vDEF'
    ) {
        $sheetArray = is_array($T3FlexForm_array) ? $T3FlexForm_array['data'][$sheet][$language] : '';
        if (is_array($sheetArray)) {
            return $this->getFlexFormValueFromSheetArray($sheetArray, explode('/', $fieldName), $value);
        }
        return '';
    }

    /**
     * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
     *
     * @param array $sheetArray Multidimensional array, typically FlexForm contents
     * @param array $fieldNameArr Array where each value points to a key in the FlexForms content - the input array will have the value returned pointed to by these keys. All integer keys will not take their integer counterparts, but rather traverse the current position in the array an return element number X (whether this is right behavior is not settled yet...)
     * @param string $value Value for outermost key, typ. "vDEF" depending on language.
     *
     * @return mixed The value, typ. string.
     * @access private
     * @see pi_getFlexFormValue()
     */
    protected function getFlexFormValueFromSheetArray($sheetArray, $fieldNameArr, $value)
    {
        $tempArr = $sheetArray;
        foreach ($fieldNameArr as $k => $v) {
            $checkedValue = MathUtility::canBeInterpretedAsInteger($v);
            if ($checkedValue) {
                if (is_array($tempArr)) {
                    $c = 0;
                    foreach ($tempArr as $values) {
                        if ($c == $v) {
                            $tempArr = $values;
                            break;
                        }
                        $c++;
                    }
                }
            } else {
                $tempArr = $tempArr[$v];
            }
        }
        if (is_array($tempArr)) {
            if (is_array($tempArr['el'])) {
                $out = $this->getFlexformSectionsRecursively($tempArr['el'], $value);
            } else {
                $out = $tempArr[$value];
            }
        } else {
            $out = $tempArr;
        }

        return $out;
    }

    /**
     * @param $dataArr
     * @param string $valueKey
     *
     * @return array
     */
    protected function getFlexformSectionsRecursively($dataArr, $valueKey = 'vDEF')
    {
        $out = [];
        foreach ($dataArr as $k => $el) {
            if (is_array($el) && is_array($el['el'])) {
                $out[$k] = $this->getFlexformSectionsRecursively($el['el']);
            } elseif (is_array($el) && is_array($el['data']['el'])) {
                $out[] = $this->getFlexformSectionsRecursively($el['data']['el']);
            } elseif (isset($el[$valueKey])) {
                $out[$k] = $el[$valueKey];
            }
        }

        return $out;
    }
}

<?php

namespace SourceBroker\Hugo\ContextMenu\ItemProviders;

use SourceBroker\Hugo\Utility\DomainUtility;
use SourceBroker\Hugo\Utility\RootlineUtility;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Item provider adding Hello World item
 */
class PageView extends AbstractProvider
{
    /**
     * @var array
     */
    protected $itemsConfiguration = [];

    /**
     * @var array
     */
    protected $hugoViewItemBaseConfiguration = [
        'type' => 'item',
        'label' => 'LLL:EXT:hugo/Resources/Private/Language/locallang_db.xlf:context_menu.pageView.label',
        'iconIdentifier' => 'actions-document-view',
        'callbackAction' => 'viewPage'
    ];

    /**
     * @return bool
     */
    public function canHandle(): bool
    {
        return $this->table === 'pages';
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 77;
    }

    /**
     * @param array $items
     * @return array
     */
    public function addItems(array $items): array
    {
        if (!array_key_exists('view', $items)) {
            return $items;
        }

        $this->setHugoViewItemsConfiguration();

        $localItems = $this->prepareItems($this->itemsConfiguration);
        $position = array_search('view', array_keys($items), true);
        $beginning = array_slice($items, 0, $position + 1, true);
        $end = array_slice($items, $position, null, true);

        return $beginning + $localItems + $end;
    }

    /**
     * @param string $itemName
     * @param string $type
     * @return bool
     */
    protected function canRender(string $itemName, string $type): bool
    {
        return true;
    }

    /**
     * @param string $itemName
     *
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        if (!isset($this->itemsConfiguration[$itemName])) {
            return [];
        }

        $itemConfiguration = $this->itemsConfiguration[$itemName];

        if (empty($itemConfiguration['txHugoDomain']) || empty($itemConfiguration['txHugoPageUid'])) {
            return [];
        }

        $rootLineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $itemConfiguration['txHugoPageUid']);

        return [
            'data-callback-module' => 'TYPO3/CMS/Hugo/ContextMenuActions',
            'data-preview-url' => 'http://' . $itemConfiguration['txHugoDomain'] . '/' . $rootLineUtility->getSlugifiedRootlinePath(),
        ];
    }

    /**
     * @return void
     */
    protected function setHugoViewItemsConfiguration(): void
    {
        $domainUtility = GeneralUtility::makeInstance(DomainUtility::class);
        $domains = $domainUtility->getHugoDomainsForPid($this->identifier);

        foreach ($domains as $index => $domain) {
            $this->itemsConfiguration['hugoView' . $index] = array_merge(
                $this->hugoViewItemBaseConfiguration,
                [
                    'label' => $this->getHugoViewItemLabel($domain),
                    'txHugoDomain' => $domain,
                    'txHugoPageUid' => $this->identifier,
                ]
            );
        }
    }

    /**
     * @param $domain
     *
     * @return string
     */
    protected function getHugoViewItemLabel($domain): string
    {
        return sprintf(
            GeneralUtility::makeInstance(LanguageService::class)->sL($this->hugoViewItemBaseConfiguration['label']),
            strlen($domain) > 25 ? substr($domain, 0, 11) . '...' . substr($domain, -11) : $domain
        );
    }
}

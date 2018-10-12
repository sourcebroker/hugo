<?php

namespace SourceBroker\Hugo\Hooks;

use SourceBroker\Hugo\Utility\DomainUtility;
use SourceBroker\Hugo\Utility\RootlineUtility;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ButtonBar
 */
class ButtonBar
{
    /**
     * If `append` new buttons will be displayed after default preview button.
     * If `replace` new buttons will be displayed instead default preview button.
     * @todo make it better configurable.
     * @var string
     */
    private $inputType = 'replace';

    /**
     * @param array $buttons
     * @param \TYPO3\CMS\Backend\Template\Components\ButtonBar $buttonBar
     *
     * @return array
     */
    public function getButtons(array $buttons, \TYPO3\CMS\Backend\Template\Components\ButtonBar $buttonBar)
    {
        foreach ($buttons['buttons'] ?? [] as $buttonPosition => $buttonGroups) {
            foreach ($buttonGroups ?? [] as $buttonGroup => $btns) {
                if (!is_array($btns)) {
                    continue;
                }

                $pageViewPos = array_search(
                    'actions-view-page',
                    array_map(
                        function ($linkButton) {
                            return $linkButton instanceof LinkButton ? $linkButton->getIcon()->getIdentifier() : '';
                        }, $btns
                    )
                );

                if (!is_numeric($pageViewPos)) {
                    continue;
                }

                /** @var LinkButton $defaultPageViewButton */
                $defaultPageViewButton = $buttons['buttons'][$buttonPosition][$buttonGroup][$pageViewPos];

                // @todo find better way to get the current page ID. Maybe read it from already existing view button?
                $pageUid = (int)$_GET['id'];

                $domainUtility = GeneralUtility::makeInstance(DomainUtility::class);
                $hugoDomains = $domainUtility->getHugoDomainsForPid($pageUid);
                $hugoViewButtons = [];

                $rootLineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageUid);

                foreach ($hugoDomains as $hugoDomain) {
                    $previewUrl = 'http://' . $hugoDomain . '/' . $rootLineUtility->getSlugifiedRootlinePath();

                    $onclickCode = sprintf(
                        'var previewWin = window.open(%s,\'newTYPO3frontendWindow\');'
                        . 'if (previewWin.location.href === %s) { previewWin.location.reload(); };',
                        GeneralUtility::quoteJSvalue($previewUrl),
                        GeneralUtility::quoteJSvalue($previewUrl)
                    );

                    $hugoViewButtons[] = $buttonBar->makeLinkButton()
                        ->setOnClick($onclickCode)
                        ->setTitle($this->inputType === 'replace'
                            ? $defaultPageViewButton->getTitle()
                            : $this->getHugoViewItemLabel($hugoDomain)
                        )
                        ->setIcon($this->getIconFactory()->getIcon('actions-view-page', Icon::SIZE_SMALL))
                        ->setHref('#');
                }

                array_splice(
                    $buttons['buttons'][$buttonPosition][$buttonGroup],
                    $this->inputType === 'replace' ? $pageViewPos : $pageViewPos + 1,
                    $this->inputType === 'replace' ? 1 : 0,
                    $hugoViewButtons
                );
            }
        }

        return $buttons['buttons'];
    }

    /**
     * @return IconFactory
     */
    protected function getIconFactory(): IconFactory
    {
        static $iconFactory;

        if (!$iconFactory instanceof IconFactory) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        }

        return $iconFactory;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        static $languageService;

        if (!$languageService instanceof LanguageService) {
            $languageService = GeneralUtility::makeInstance(LanguageService::class);
        }

        return $languageService;
    }

    /**
     * @param $domain
     *
     * @return string
     */
    protected function getHugoViewItemLabel($domain): string
    {
        return sprintf(
            $this->getLanguageService()
                ->sL('LLL:EXT:hugo/Resources/Private/Language/locallang_db.xlf:context_menu.pageView.label'),
            $domain
        );
    }
}

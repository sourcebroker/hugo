<?php
declare(strict_types=1);

namespace SourceBroker\Hugo\Typolink;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use SourceBroker\Hugo\Domain\Repository\Typo3PageRepository;
use SourceBroker\Hugo\Utility\RootlineUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Builds a TypoLink to a certain page
 */
class PageLinkBuilder extends AbstractTypolinkBuilder
{
    /**
     * @inheritdoc
     */
    public function build(array &$linkData, string $linkText, string $target, array $conf): array
    {
        $url = null;

        // support for some legacy links to the current page
        // @see \TYPO3\CMS\Core\LinkHandling\LegacyLinkNotationConverter::resolvePageRelatedParameters
        $pageUid = ($linkData['pageuid'] !== 'current')
            ? (int)$linkData['pageuid']
            : $this->txHugoConfigurator->getPageUid();

        if ($pageUid) {
            $page = GeneralUtility::makeInstance(Typo3PageRepository::class)->getByUid($pageUid);
            if ($page['hidden'] === 0 && $page['deleted'] === 0) {
                $url = GeneralUtility::makeInstance(RootlineUtility::class, $pageUid)
                    ->getSlugifiedRootlinePath($this->txHugoSysLanguageUid);

                if (isset($linkData['fragment'])) {
                    $url .= '#' . $linkData['fragment'];
                }
            }
        }
        return [
            $url === null ? null : $this->applyHugoProcessors($url),
            (empty($linkText) && !empty($page['title'])) ? $page['title'] : $linkText,
            $target
        ];
    }

    /**
     * @return callable[]
     */
    protected function getProcessors(): array
    {
        return array_merge(
            parent::getProcessors(),
            [
                [$this, 'addHugoLanguagePrefix'],
                [$this, 'addHugoAbsRelPrefix'],
            ]
        );
    }
}

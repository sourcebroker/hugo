<?php
declare(strict_types = 1);
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

use Cocur\Slugify\Slugify;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * Builds a TypoLink to a certain page
 */
class PageLinkBuilder extends AbstractTypolinkBuilder
{
    /**
     * @inheritdoc
     */
    public function build(array &$linkDetails, string $linkText, string $target, array $conf): array
    {
        $rootline = GeneralUtility::makeInstance(RootlineUtility::class, $linkDetails['pageuid'])->get();
        $page = current($rootline);
        parse_str($conf['additionalParams'] ?? '', $additionalParamsParts);
        $languageUid = $additionalParamsParts['L'] ?? $page['sys_language_uid'];
        $aliases = array_map(function (array $page) use ($languageUid) {
            if (!$page['is_siteroot']) {
                // TODO: use $languageUid to make translated path
                return Slugify::create()->slugify($page['title']);
            }
        }, array_reverse($rootline));
        $url = '/' . implode('/', array_filter($aliases)) . '/';
        return [$url, $page['title']];
    }
}

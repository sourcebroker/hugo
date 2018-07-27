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
use TYPO3\CMS\Core\Utility\MathUtility;

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
        $pageUid = $linkData['pageuid'];
        if (MathUtility::canBeInterpretedAsInteger($pageUid)) {
            $page = GeneralUtility::makeInstance(Typo3PageRepository::class)->getByUid((int)$pageUid);
            if ($page['hidden'] === 0 && $page['deleted'] === 0) {
                $url = GeneralUtility::makeInstance(RootlineUtility::class, $pageUid)->getSlugifiedRootlinePath();
            }
        }

        return [
            $this->addAbsRelPrefix($url),
            empty($linkText) ? $page['title'] : $linkText,
            $target,
        ];
    }
}

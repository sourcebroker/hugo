<?php

/***************************************************************
 *  Copyright notice
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace SourceBroker\Hugo\Service;

use SourceBroker\Hugo\Typolink\UnableToLinkException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownUrnException;

class Typo3UrlService
{
    /**
     * TODO: implement support for multilang
     *
     * @param string $linkText
     * @param $linkParameter
     * @param int $pageLanguageUid
     *
     * @return array|null
     */
    public function linkArray($linkText = '', $linkParameter, int $pageLanguageUid = null): ?array
    {
        // $pageLanguageUid TODO: implement support for multilang

        $linkData = GeneralUtility::makeInstance(TypoLinkCodecService::class)->decode($linkParameter);
        if (!is_array($linkData)) {
            return $linkData;
        }
        $linkParameter = $linkData['url'];

        if (!empty($linkParameter)) {
            $linkService = GeneralUtility::makeInstance(LinkService::class);
            try {
                $linkDetails = $linkService->resolve($linkParameter);
            } catch (UnknownUrnException $exception) {
                $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
                $logger->warning('The link could not be generated', ['exception' => $exception]);
            }

            $linkDetails['typoLinkParameter'] = $linkParameter;
            if (isset($linkDetails['type']) && isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['EXTCONF']['typolinkBuilder'][$linkDetails['type']])) {
                $linkBuilder = GeneralUtility::makeInstance(
                    $GLOBALS['TYPO3_CONF_VARS']['EXT']['EXTCONF']['typolinkBuilder'][$linkDetails['type']],
                    GeneralUtility::makeInstance(ContentObjectRenderer::class)
                );
                try {
                    list($url, $linkData['linkText'], $linkData['target']) = $linkBuilder->build($linkDetails, $linkText, $linkData['target'], []);
                } catch (UnableToLinkException $e) {
                    $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
                    $logger->debug(sprintf('Unable to link "%s": %s', $e->getLinkText(), $e->getMessage()),
                        ['exception' => $e]);
                    $url = $e->getLinkText();
                }
            } elseif (isset($linkDetails['url'])) {
                $url = $linkDetails['url'];
            } else {
                $url = $linkText;
            }
            $linkData['href'] = $url;
            unset($linkData['additionalParams']);
            unset($linkData['url']);
            $linkData['tag'] = '<a ' . GeneralUtility::implodeAttributes($linkData) . '>' . $linkText . '</a>';
        }
        return empty($url) ? [] : $linkData;
    }
}

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

use SourceBroker\Hugo\Configuration\Configurator;
use SourceBroker\Hugo\Typolink\AbstractTypolinkBuilder;
use SourceBroker\Hugo\Typolink\UnableToLinkException;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownUrnException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

/**
 * Class Typo3UrlService
 *
 * @package SourceBroker\Hugo\Service
 */
class Typo3UrlService
{
    /**
     * TODO: implement support for multilang
     *
     * @param string $linkParameter
     * @param Configurator $configurator
     * @param int $pageLanguageUid
     * @param string $linkText
     *
     * @return array
     */
    public function convertToLinkElement(
        string $linkParameter,
        Configurator $configurator,
        int $pageLanguageUid,
        string $linkText = ''
    ): array {
        $linkData = GeneralUtility::makeInstance(TypoLinkCodecService::class)->decode($linkParameter);
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
                /** @var AbstractTypolinkBuilder $linkBuilder */
                $linkBuilder = GeneralUtility::makeInstance(
                    $GLOBALS['TYPO3_CONF_VARS']['EXT']['EXTCONF']['typolinkBuilder'][$linkDetails['type']],
                    GeneralUtility::makeInstance(ContentObjectRenderer::class),
                    $configurator,
                    (int)$pageLanguageUid ?: 0
                );
                try {
                    list($url, $linkData['linkText'], $linkData['target']) = $linkBuilder->build(
                        $linkDetails,
                        $linkText,
                        $linkData['target'],
                        []
                    );
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
            $linkData['title'] = htmlspecialchars($linkData['title']);
            $linkData['tag'] = '<a ' . GeneralUtility::implodeAttributes(
                    array_filter($linkData, function ($key) {
                        return !in_array($key, ['additionalParams', 'url', 'linkText']);
                    }, ARRAY_FILTER_USE_KEY)
                ) .
                '>' . $linkData['linkText'] . '</a>';
        }

        return empty($url) ? [] : $linkData;
    }

}

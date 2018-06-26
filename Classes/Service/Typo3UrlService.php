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

use Cocur\Slugify\Slugify;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

class Typo3UrlService
{

    /**
     * @var \TYPO3\CMS\Core\Charset\CharsetConverter
     */
    protected $csConvertor;

    /**
     * @param \TYPO3\CMS\Core\Charset\CharsetConverter $csConvertor
     */
    public function injectCsConvertor(\TYPO3\CMS\Core\Charset\CharsetConverter $csConvertor)
    {
        $this->csConvertor = $csConvertor;
    }

    /**
     * @param     $config
     * @param int $pageLanguageUid
     *
     * @return array|null
     */
    public function linkArray($config, int $pageLanguageUid): ?array
    {
        if (is_string($config)) {
            $config = $this->buildTypolinkParams($config);
        }

        if (!is_array($config) || !$config) {
            return null;
        }

        $additionalParams = $config['additionalParams'] ?? '';

        parse_str(ltrim($additionalParams, '&'), $queryParts);

        if (!$queryParts['L']) {
            $queryParts['L'] = $pageLanguageUid;
        }

        $config['additionalParams'] = '&'.http_build_query($queryParts);

        $linkService = GeneralUtility::makeInstance(LinkService::class);

        try {
            $linkDetails = $linkService->resolve($config['href']);
        } catch (InvalidPathException $exception) {
            return null;
        }

        list($href, $linkText) = $this->buildLink($linkDetails, $config);

        $config['href'] = $href;
        $config['tag'] = $this->buildTag($config, $linkText);

        return $config;
    }

    protected function buildTypolinkParams(string $link): array
    {
        $config = [];
        if ( ! empty($link)) {
            $linkParameterParts = GeneralUtility::makeInstance(TypoLinkCodecService::class)->decode($link);

            list($linkHandlerKeyword, $linkHandlerValue) = explode(':', $linkParameterParts['url'], 2);
            $linkParameter = $linkParameterParts['url'];

            $config = [
                'href' => $linkParameter,
                'target' => $linkParameterParts['target'],
                'class' => $linkParameterParts['class'],
                'title' => $linkParameterParts['title'],
            ];

            // additional parameters that need to be set
            if ($linkParameterParts['additionalParams'] !== '') {
                $forceParams = $linkParameterParts['additionalParams'];
                // params value
                $config['additionalParams'] .= $forceParams[0] === '&' ? $forceParams : '&' . $forceParams;
            }
        }

        return $config;
    }

    /**
     * @param array $linkDetails
     * @param array $config
     *
     * @return array
     */
    protected function buildLink(array $linkDetails, array $config): array
    {
        $linkType = $linkDetails['type'];

        switch ($linkType) {
            case 'page':
                return $this->buildPageLink($linkDetails, $config);
        }

        return [];
    }

    /**
     * @param array $linkDetails
     * @param array $config
     *
     * @return array
     */
    protected function buildPageLink(array $linkDetails, array $config): array
    {
        $pageUid = $linkDetails['pageuid'];

        $rootline = GeneralUtility::makeInstance(RootlineUtility::class, $pageUid)->get();
        $page = current($rootline);
        $additionalParams = $config['additionalParams'];
        parse_str($additionalParams, $additionalParamsParts);
        $languageUid = $additionalParamsParts['L'] ?? $page['sys_language_uid'];

        $aliases = array_map(function (array $page) use ($languageUid) {
            if (!$page['is_siteroot']) {
                return $this->slug($page['title']);
            }
        }, array_reverse($rootline));

        $url = '//'.$this->getDomainFor((int)$rootline[0]['uid']).'/'.implode('/', array_filter($aliases)).'/';

        return [$url, $page['title']];
    }

    /**
     * @param array       $config
     * @param string|null $linkText
     *
     * @return string
     */
    protected function buildTag(array $config, string $linkText = null): string
    {
        return '<a ' . GeneralUtility::implodeAttributes($config) . '>'.$linkText.'</a>';
    }

    /**
     * @param int $pageUid
     *
     * @return string
     */
    protected function getDomainFor(int $pageUid) : string
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_domain');

        $domain = $qb->select('domainName')
            ->from('sys_domain')
            ->andWhere(
                $qb->expr()->eq('pid', $qb->createNamedParameter($pageUid))
            )
            ->orderBy('sorting', 'ASC')
            ->execute()
            ->fetch();

        if (!$domain) {
            throw new \RuntimeException('Missing domain for root page "'.$pageUid.'" ');
        }

        return $domain['domainName'];
    }

    /**
     * @param string $title
     *
     * @return string
     */
    protected function slug(string $title)
    {
        return Slugify::create()->slugify($title);
    }
}

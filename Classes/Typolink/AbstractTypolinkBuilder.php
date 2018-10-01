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

use SourceBroker\Hugo\Configuration\Configurator;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Abstract class to provide proper helper for most types necessary
 * Hands in the contentobject which is needed here for all the stdWrap magic.
 */
abstract class AbstractTypolinkBuilder extends \TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder
{
    /**
     * @var Configurator
     */
    protected $txHugoConfigurator;

    /**
     * @var int
     */
    protected $txHugoSysLanguageUid = 0;

    /**
     * @var array
     */
    protected static $linksCache = [];

    /**
     * AbstractTypolinkBuilder constructor.
     *
     * @param ContentObjectRenderer $contentObjectRenderer
     * @param Configurator $txHugoConfigurator
     * @param int $txHugoSysLanguageUid
     */
    public function __construct(
        ContentObjectRenderer $contentObjectRenderer,
        Configurator $txHugoConfigurator,
        int $txHugoSysLanguageUid = 0
    ) {
        parent::__construct($contentObjectRenderer);
        $this->txHugoConfigurator = $txHugoConfigurator;
        $this->txHugoSysLanguageUid = $txHugoSysLanguageUid;
    }

    /**
     * Overwrites parent method to make sure that TSFE is not used
     *
     * @return TypoScriptFrontendController
     *
     * @throws \Exception
     */
    public function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        throw new \Exception('TSFE can not be initialized');
    }

    /**
     * Overwrites method to get the link target to not use TSFE inside of it
     *
     * {@inheritdoc}
     */
    protected function resolveTargetAttribute(
        array $conf,
        string $name,
        bool $respectFrameSetOption = false,
        string $fallbackTarget = ''
    ): string {
        if (isset($conf[$name])) {
            $target = $conf[$name];
        } else {
            $target = $fallbackTarget;
        }
        if ($conf[$name . '.']) {
            $target = (string)$this->contentObjectRenderer->stdWrap($target, $conf[$name . '.']);
        }

        return $target;
    }

    /**
     * @return callable[]
     */
    protected function getProcessors(): array
    {
        return [];
    }

    /**
     * @param string $url
     *
     * @param array $linkDetails
     * @return string
     */
    protected function applyHugoProcessors(string $url, array $linkDetails = []): string
    {
        /** @var callable $processor */
        foreach ($this->getProcessors() as $processor) {
            $url = $processor($url, $linkDetails);
        }
        return $url;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    protected function addHugoAbsRelPrefix(string $url): string
    {
        return $this->txHugoConfigurator->getOption('link.absRefPrefix') . $url;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    protected function addHugoLanguagePrefix(string $url): string
    {
        $langPrefix = $this->txHugoConfigurator->getOption('languages.' . $this->txHugoSysLanguageUid);

        if (empty($this->txHugoSysLanguageUid) || empty($langPrefix)) {
            return $url;
        }

        return $langPrefix . '/' . $url;
    }

    /**
     * @param string $url
     * @param $linkDetails
     * @return string
     */
    protected function addLinkToLinksFile(string $url, $linkDetails): string
    {
        if ($linkDetails['type'] === 'page') {
            $pageUid = ($linkDetails['pageuid'] !== 'current')
                ? (int)$linkDetails['pageuid']
                : $this->txHugoConfigurator->getPageUid();
            $key = 'page:' . $pageUid;
            $storeLinksFile = PATH_site . $this->txHugoConfigurator->getOption('writer.path.data') . '/links.yaml';
            if (count(self::$linksCache) === 0 && file_exists($storeLinksFile)) {
                unlink($storeLinksFile);
            }
            if (!array_key_exists($key, self::$linksCache)) {
                file_put_contents($storeLinksFile,
                    '\'' . $key . '\': \'' . $url . "'\n", FILE_APPEND);
                self::$linksCache[$key] = true;
            }
        }
        return $url;
    }
}

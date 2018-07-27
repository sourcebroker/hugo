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
     * AbstractTypolinkBuilder constructor.
     *
     * @param ContentObjectRenderer $contentObjectRenderer
     * @param Configurator $txHugoConfigurator
     */
    public function __construct(
        ContentObjectRenderer $contentObjectRenderer,
        Configurator $txHugoConfigurator
    )
    {
        parent::__construct($contentObjectRenderer);
        $this->txHugoConfigurator = $txHugoConfigurator;
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
    protected function resolveTargetAttribute(array $conf, string $name, bool $respectFrameSetOption = false, string $fallbackTarget = ''): string
    {
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
     * @param string $url
     *
     * @return string;
     */
    protected function addAbsRelPrefix(string $url)
    {
        return $this->txHugoConfigurator->getOption('link.absRefPrefix').$url;
    }
}

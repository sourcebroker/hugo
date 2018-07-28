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

use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Http\UrlProcessorInterface;

/**
 * Builds a TypoLink to an email address
 */
class EmailLinkBuilder extends AbstractTypolinkBuilder
{
    /**
     * @inheritdoc
     */
    public function build(array &$linkDetails, string $linkText, string $target, array $conf): array
    {
        list($url, $linkText) = $this->getMailTo($linkDetails['email'], $linkText);

        return [$this->applyHugoProcessors($url), $linkText, $target];
    }

    /**
     * Overwrites \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getMailTo() method to not use TSFE inside.
     *
     * Creates a href attibute for given $mailAddress.
     * The function uses spamProtectEmailAddresses for encoding the mailto statement.
     * If spamProtectEmailAddresses is disabled, it'll just return a string like "mailto:user@example.tld".
     *
     * @param string $mailAddress Email address
     * @param string $linktxt Link text, default will be the email address.
     *
     * @return string[] Returns a numerical array with two elements: 1) $mailToUrl, string ready to be inserted into the href attribute of the <a> tag, b) $linktxt: The string between starting and ending <a> tag.
     */
    protected function getMailTo($mailAddress, $linktxt)
    {
        $mailAddress = (string)$mailAddress;
        if ((string)$linktxt === '') {
            $linktxt = htmlspecialchars($mailAddress);
        }

        $originalMailToUrl = 'mailto:' . $mailAddress;
        $mailToUrl = $this->processUrl(UrlProcessorInterface::CONTEXT_MAIL, $originalMailToUrl);

        // no processing happened, therefore, the default processing kicks in
        if ($mailToUrl === $originalMailToUrl) {
            if ($this->txHugoConfigurator->getOption('link.spamProtectEmailAddresses')) {
                $mailToUrl = $this->encryptEmail(
                    $mailToUrl,
                    $this->txHugoConfigurator->getOption('link.spamProtectEmailAddresses')
                );
                if ($this->txHugoConfigurator->getOption('link.spamProtectEmailAddresses') !== 'ascii') {
                    $mailToUrl = 'javascript:linkTo_UnCryptMailto(' . GeneralUtility::quoteJSvalue($mailToUrl) . ');';
                }
                $atLabel = trim($this->txHugoConfigurator->getOption('link.spamProtectEmailAddresses_atSubst')) ?: '(at)';
                $spamProtectedMailAddress = str_replace('@', $atLabel, htmlspecialchars($mailAddress));
                if ($this->txHugoConfigurator->getOption('link.spamProtectEmailAddresses_lastDotSubst')) {
                    $lastDotLabel = trim($this->txHugoConfigurator->getOption('link.spamProtectEmailAddresses_lastDotSubst'));
                    $lastDotLabel = $lastDotLabel ? $lastDotLabel : '(dot)';
                    $spamProtectedMailAddress = preg_replace('/\\.([^\\.]+)$/', $lastDotLabel . '$1', $spamProtectedMailAddress);
                }
                $linktxt = str_ireplace($mailAddress, $spamProtectedMailAddress, $linktxt);
            }
        }

        return [$mailToUrl, $linktxt];
    }

    /**
     * Calls \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::encryptEmail method using ReflectionMethod,
     * because method is protected by default in TYPO3 core.
     *
     * @param string $string Input string to en/decode: "mailto:blabla@bla.com
     * @param mixed  $type - either "ascii" or a number between -10 and 10, taken from config.spamProtectEmailAddresses
     * @return string encoded version of $string
     */
    protected function encryptEmail($string, $type)
    {
        try {
            $encryptEmail = new \ReflectionMethod(ContentObjectRenderer::class, 'encryptEmail');
            $encryptEmail->setAccessible(true);

            return $encryptEmail->invoke($this->contentObjectRenderer, $string, $type);
        } catch (\ReflectionException $exception) {
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
            $logger->warning('Email address could not be encrypted', ['exception' => $exception]);
        }

        return $string;
    }
}

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

use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Abstract class to provide proper helper for most types necessary
 * Hands in the contentobject which is needed here for all the stdWrap magic.
 */
abstract class AbstractTypolinkBuilder extends \TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder
{
    /**
     * TODO: remove need for TSFE
     * @return TypoScriptFrontendController
     */
    public function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        if (!$GLOBALS['TSFE']) {
            // This usually happens when typolink is created by the TYPO3 Backend, where no TSFE object
            // is there. This functionality is currently completely internal, as these links cannot be
            // created properly from the Backend.
            // However, this is added to avoid any exceptions when trying to create a link
            $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
                TypoScriptFrontendController::class,
                    [],
                    (int)GeneralUtility::_GP('id'),
                    (int)GeneralUtility::_GP('type')
            );
            $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance(PageRepository::class);
            $GLOBALS['TSFE']->sys_page->init(false);
            $GLOBALS['TSFE']->tmpl = GeneralUtility::makeInstance(TemplateService::class);
            $GLOBALS['TSFE']->tmpl->init();
        }
        return $GLOBALS['TSFE'];
    }
}

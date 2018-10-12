<?php

namespace SourceBroker\Hugo\Hooks;

class BackendHook
{
    /**
     * Register javascript modules to run export process
     */
    public function registerBackendJavaScriptsModules(): void
    {
        $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        // For TYPO3 lower than 9 is used legacy version of export script
        if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) < 9000000) {
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/Hugo/ExportLegacy');
        } else {
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/Hugo/Export');
        }
    }
}

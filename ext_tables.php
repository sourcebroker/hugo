<?php

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function () {
        if (TYPO3_MODE === 'BE') {
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                'SourceBroker.Hugo',
                // have to be `web` instead of `tools`, because it is needed the selected page UID to determine pageTsConfig for hugo
                'web',
                'mod1',
                '',
                [
                    'Administration' => 'index,export,exportedStructurePreview,systemEnvironmentCheckAction',
                ],
                [
                    'access' => 'user,group',
                    'icon' => 'EXT:hugo/Resources/Public/Icons/be-module-administration.svg',
                    'labels' => 'LLL:EXT:hugo/Resources/Private/Language/locallang_mod.xlf',
                ]
            );
            
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler (
                'HugoAdministrationController::export',
                \SourceBroker\Hugo\Controller\AdministrationController::class.'->exportAjax'
            );
        }
    }
);

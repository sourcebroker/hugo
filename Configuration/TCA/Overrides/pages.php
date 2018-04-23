<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'pages',
    [
        'tx_hugo_menuid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:hugo/Resources/Private/Language/locallang_db.xlf:pages.tx_hugo_menuid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'items' => [
                    ['Menu main', 'menu-main'],
                    ['Menu header', 'menu-header'],
                    ['Menu footer', 'menu-footer'],
                ],
            ],
            'size' => 5,
            'maxitems' => 100,
            'minitems' => 0,
        ],
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    'tx_hugo_menuid',
    '',
    'after:backend_layout'
);


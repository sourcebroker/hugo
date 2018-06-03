<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tx_dce_domain_model_dce',
    [
        'tx_hugo_typename' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:hugo/Resources/Private/Language/locallang_db.xlf:pages.tx_hugo_typename',
            'config' => [
                'type' => 'input',
            ],
        ],
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tx_dce_domain_model_dce',
    'tx_hugo_typename'
);


<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'sys_domain',
    [
        'tx_hugo_domains' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:hugo/Resources/Private/Language/locallang_db.xlf:sys_domain.tx_hugo_domains',
            'config' => [
                'type' => 'input',
            ],
        ],
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'sys_domain',
    '--div--;Hugo,tx_hugo_domains'
);

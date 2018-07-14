<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'pages',
    [
        'tx_hugo_frontmatter' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:hugo/Resources/Private/Language/locallang_db.xlf:pages.tx_hugo_frontmatter',
            'config' => [
                'type' => 'user',
                'userFunc' => \SourceBroker\Hugo\Tca\Pages::class . '->getHugoFrontMatter',
            ],
        ],
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;Hugo,tx_hugo_frontmatter'
);


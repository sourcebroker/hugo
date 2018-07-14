<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        'tx_hugo_frontmatter' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:hugo/Resources/Private/Language/locallang_db.xlf:tt_content.tx_hugo_frontmatter',
            'config' => [
                'type' => 'user',
                'userFunc' => \SourceBroker\Hugo\Tca\TtContent::class . '->getHugoFrontMatter',
            ],
        ],
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;Hugo,tx_hugo_frontmatter'
);


<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "hugo".
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Hugo exporter (gohugo.io)',
    'description' => 'Export TYPO3 tree structure (with content) to hugo format.',
    'category' => 'be',
    'version' => '0.0.1',
    'state' => 'beta',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearcacheonload' => false,
    'author' => 'SourceBroker Team',
    'author_email' => 'office@sourcebroker.net',
    'author_company' => 'SourceBroker',
    'constraints' =>
        [
            'depends' =>
                [
                    'typo3' => '8.7.0-8.7.99',
                ],
            'conflicts' =>
                [],
            'suggests' =>
                [],
        ],
];

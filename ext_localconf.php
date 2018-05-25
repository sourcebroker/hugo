<?php

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {

    if (TYPO3_MODE !== 'FE') {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:hugo/Configuration/TsConfig/Page/tx_hugo.tsconfig">'
        );
    }
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][]
        = \SourceBroker\Hugo\Command\HugoCommandController::class;

    /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
    $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
    $signalSlotDispatcher->connect(
        \SourceBroker\Hugo\Traversing\PageTraverser::class,
        'document',
        \SourceBroker\Hugo\Indexer\PageIndexer::class,
        'run'
    );

//    Plugin indexer - TODO
//    $signalSlotDispatcher->connect(
//        \SourceBroker\Hugo\Traversing\PageTraverser::class,
//        'extractDocuments',
//        \SourceBroker\Hugo\Indexer\RecordIndexer::class,
//        'runCollection'
//    );
});





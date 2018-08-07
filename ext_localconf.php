<?php

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () use ($_EXTKEY) {

    if (TYPO3_MODE !== 'FE') {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:hugo/Configuration/TsConfig/Page/tx_hugo.tsconfig">'
        );
    }

    if (TYPO3_MODE === 'BE') {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \SourceBroker\Hugo\Command\HugoCommandController::class;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][] = \SourceBroker\Hugo\ContextMenu\ItemProviders\PageView::class;
    }

    /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
    $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
    $signalSlotDispatcher->connect(
        \SourceBroker\Hugo\Traversing\TreeTraverser::class,
        'getDocumentsForPage',
        \SourceBroker\Hugo\Indexer\PageIndexer::class,
        'getDocumentsForPage'
    );

    $signalSlotDispatcher->connect(
        \SourceBroker\Hugo\Traversing\TreeTraverser::class,
        'getDocumentsForPage',
        \SourceBroker\Hugo\Indexer\RecordIndexer::class,
        'getDocumentsForPage'
    );

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['hugo'] = 'SourceBroker\\Hugo\\DataHandling\\DataHandler';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['hugo'] = 'SourceBroker\\Hugo\\DataHandling\\DataHandler';

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\SourceBroker\Hugo\Task\ExportTask::class] = [
        'extension' => $_EXTKEY,
        'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:task.export.title',
        'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:task.export.description',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\SourceBroker\Hugo\Task\ExportPagesTask::class] = [
        'extension' => $_EXTKEY,
        'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:task.exportPages.title',
        'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:task.exportPages.description',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\SourceBroker\Hugo\Task\ExportMediaTask::class] = [
        'extension' => $_EXTKEY,
        'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:task.exportMedia.title',
        'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:task.exportMedia.description',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\SourceBroker\Hugo\Task\ExportContentTask::class] = [
        'extension' => $_EXTKEY,
        'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:task.exportContent.title',
        'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:task.exportContent.description',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'][] = \SourceBroker\Hugo\Hooks\ProcessHook::class;

    $GLOBALS['TYPO3_CONF_VARS']['EXT']['EXTCONF']['typolinkBuilder'] = [
        'page' => \SourceBroker\Hugo\Typolink\PageLinkBuilder::class,
        'file' => \SourceBroker\Hugo\Typolink\FileOrFolderLinkBuilder::class,
        'folder' => \SourceBroker\Hugo\Typolink\FileOrFolderLinkBuilder::class,
        'url' => \SourceBroker\Hugo\Typolink\ExternalUrlLinkBuilder::class,
        'email' => \SourceBroker\Hugo\Typolink\EmailLinkBuilder::class,
        'record' => \SourceBroker\Hugo\Typolink\DatabaseRecordLinkBuilder::class,
        'unknown' => \SourceBroker\Hugo\Typolink\LegacyLinkBuilder::class,
    ];

});





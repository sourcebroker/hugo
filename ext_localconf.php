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
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook'][] =
            \SourceBroker\Hugo\Hooks\ButtonBar::class . '->getButtons';
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

    $GLOBALS['TYPO3_CONF_VARS']['EXT']['EXTCONF']['typolinkBuilder']['page']
        = \SourceBroker\Hugo\Typolink\PageLinkBuilder::class;
    $GLOBALS['TYPO3_CONF_VARS']['EXT']['EXTCONF']['typolinkBuilder']['file']
        = \SourceBroker\Hugo\Typolink\FileOrFolderLinkBuilder::class;
    $GLOBALS['TYPO3_CONF_VARS']['EXT']['EXTCONF']['typolinkBuilder']['folder']
        = \SourceBroker\Hugo\Typolink\FileOrFolderLinkBuilder::class;
    $GLOBALS['TYPO3_CONF_VARS']['EXT']['EXTCONF']['typolinkBuilder']['url']
        = \SourceBroker\Hugo\Typolink\ExternalUrlLinkBuilder::class;
    $GLOBALS['TYPO3_CONF_VARS']['EXT']['EXTCONF']['typolinkBuilder']['email']
        = \SourceBroker\Hugo\Typolink\EmailLinkBuilder::class;
    $GLOBALS['TYPO3_CONF_VARS']['EXT']['EXTCONF']['typolinkBuilder']['record']
        = \SourceBroker\Hugo\Typolink\DatabaseRecordLinkBuilder::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess'][] = \SourceBroker\Hugo\Hooks\BackendHook::class . '->registerBackendJavaScriptsModules';

    $extbaseObjectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class);
    $extbaseObjectContainer->registerImplementation(\SourceBroker\Hugo\Queue\Storage\StorageInterface::class,
        \SourceBroker\Hugo\Queue\Storage\MySqlStorage::class);
    $extbaseObjectContainer->registerImplementation(\SourceBroker\Hugo\Queue\QueueInterface::class,
        \SourceBroker\Hugo\Queue\SimpleQueue::class);
});

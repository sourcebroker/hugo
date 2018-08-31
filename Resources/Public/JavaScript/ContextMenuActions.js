/**
 * Module: TYPO3/CMS/Hugo/ContextMenuActions
 *
 * @exports TYPO3/CMS/Hugo/ContextMenuActions
 */
define(function () {
    'use strict';

    /**
     * @exports TYPO3/CMS/Hugo/ContextMenuActions
     */
    var ContextMenuActions = {};

    /**
     * Say hello
     *
     * @param {string} table
     * @param {int} uid of the page
     */
    ContextMenuActions.viewPage = function (table, uid) {
        var $viewUrl = $(this).data('preview-url');
        if ($viewUrl) {
            var previewWin = window.open($viewUrl, 'newTYPO3frontendWindow');
            previewWin.focus();
        } else {
            top.TYPO3.Notification.error('Error', 'Page preview URL could not be determined.', 5);
        }
    };

    return ContextMenuActions;
});

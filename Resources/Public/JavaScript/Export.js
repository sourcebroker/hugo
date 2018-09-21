define([
  'jquery',
  'TYPO3/CMS/Backend/Storage',
], function($, Storage) {
  'use strict';

  var HugoExport = {
    timeout: 1000 * 5, // per 5 sec
    execute: function() {
      if (Storage.Persistent.isset('hugoExportLock')) {
        HugoExport.delay();
        return false;
      }
      Storage.Persistent.set('hugoExportLock', 1);
      $.ajax(
          TYPO3.settings.ajaxUrls['HugoAdministrationController::export']).always(function() {
        HugoExport.delay();
        HugoExport.reset();
      });
    },
    delay: function() {
      setTimeout(function() {
        HugoExport.execute();
      }, HugoExport.timeout);
    },
    reset: function() {
      Storage.Persistent.unset('hugoExportLock');
    },
  };

  HugoExport.reset();
  HugoExport.execute();
});

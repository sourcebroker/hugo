define([
  'jquery',
  'TYPO3/CMS/Backend/Storage/Persistent',
], function ($, PersistentStorage) {
  'use strict'

  var HugoExport = {
    timeout: 1000 * 5, // per 5 sec
    execute: function () {
      if (PersistentStorage.isset('hugoExportLock')) {
        HugoExport.delay()
        return false
      }
      PersistentStorage.set('hugoExportLock', 1)
      $.ajax(TYPO3.settings.ajaxUrls['hugo_admininistator_export']).
        always(function () {
          HugoExport.delay()
          HugoExport.reset()
        })
    },
    delay: function () {
      setTimeout(function () {
        HugoExport.execute()
      }, HugoExport.timeout)
    },
    reset: function () {
      PersistentStorage.unset('hugoExportLock')
    },
    setTimeout: function (val) {
      if (!isNaN(val)) {
        HugoExport.timeout = parseInt(val)
      }
    },
  }

  HugoExport.reset()
  HugoExport.execute()
})

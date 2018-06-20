TYPO3 Extension ``hugo``
######################

What does it do?
****************

This extension allows you to export TYPO3 pages, content, media in a way that is able to be consumed by TYPO3 Hugo Theme.
https://github.com/sourcebroker/hugo-typo3-theme.

Installation
************

Use composer:
 ::

  composer require sourcebroker/hugo

Usage
*****

Use CLI command:
 ::

  typo3cms hugo:export

Exported pages are stored in ./hugo/content folder. Exported content is stored in ./hugo/data/content. Exported media
are stored in ./hugo/content/-media/fileadmin (fileadmin is taken from storage record).

You can change those folder with TsConfig. Look in file
``Configuration/TsConfig/Page/tx_hugo.tsconfig`` for possible options.

Hugo binary path
****************

On each page or content editing in TYPO3 a special hook is used to update Hugo files and make a fresh build of
all pages. For this to happen you must have Hugo binary available in your $PATH. If you do not have hugo in your $PATH
then you can set the exact path with this TsConfig:
 ::

  tx_hugo {
      hugo.path.binary = /my/path/hugo
  }

If you have different path to hugo on different instances of application (beta / live) then you can use TYPO3 conditions:
 ::

  [applicationContext = */*/Live]
    tx_hugo {
        hugo.path.binary = /var/www/.local/hugo
    }
  [end]


Changelog
*********

See https://github.com/sourcebroker/hugo/blob/master/CHANGELOG.rst

TYPO3 Extension ``hugo``
######################

What does it do?
****************

This extension allows you to export TYPO3 pages and content in a way that is able to consume by TYPO3 Hugo Theme.
https://github.com/sourcebroker/hugo-typo3-theme.

This is early beta version. So far only export of pages is working but needs fine tuning.

"hugo-typo3-theme" and this extension are both in early beta so be patient with bugs!

Any help to improve the code appreciated.

Installation
************

Use composer:

::

  composer require sourcebroker/hugo

Usage
*****

On CLI run command:

::

  typo3cms hugo:export

Exported pages are storedin hugo/content folder. You can change this folder with TsConfig. Look in file
``Configuration/TsConfig/Page/tx_hugo.tsconfig`` for possible options.

Changelog
*********

See https://github.com/sourcebroker/hugo/blob/master/CHANGELOG.rst

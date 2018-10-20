Changelog
---------

master
~~~~~~
1) [BUGFIX] Disable Export.js in TYPO3 9.5 because its incompatible an doing JS errors in TYPO3 9.5 backend on login.
2) [TASK] If no pages in database then create empty configuration object.
3) [BUGFIX] Fix warning when there is no domain record.

0.7.0
~~~~~
1) [TASK] Add styleci and scrutinizer checkers settings.
2) [TASK] Apply styleci fixes.
3) [TASK] Add styleci and scrutinizer badges.
4) [TASK] Fix styleci and scrutinizer badges.
5) [BUGFIX] Add strict types checks.
6) [TASK] Remove empty file.
7) [BUGFIX] Disable passing by reference because expression can not be referenced.
8) [BUGFIX] Add strict types checks.
9) [BUGFIX] Remove not needed switch statements.
10) [BUGFIX] Check if array bbefore use in foreach / check for null vs araray for config var.
11) [TASK] Refactor ExportContentService class.
12) [BUGFIX] Fix wrong tablename for queue.
13) [TASK] Refactor getFirstRootsiteConfig function in Configurator class.
14) [TASK] Refactor cli command show result from services. Refactor BuildService / ExportMediaService / ExportPageService
15) [TASK] Refactor Configurator class.
16) [TASK] Add tests for Configurator.
16) [TASK] Extend nimut/testing-framework version for compatibility with different TYPO3 versions.
17) [TASK] Do exportAll for content/media/pages on each change. Its in queue now and its fast. Optimize that later.
18) [BREAKING] writer.path.data should point to Hugo "data" directory and not to "data/content" directory where exported content elements are stored.
19) [FEATURE] Add way to build links inside hugo templates.
20) [BUGFIX] Remove file only if exists.
21) [TASK] TYPO3 9.5 compatibility.
22) [TASK] Update registering backend ajax with version compatible with TYPO3 9.5.
23) [BUGFIX] Adapt Export AJAX action to work with TYPO3 8 and TYPO3 9

0.6.0
~~~~~
1) [BUGFIX] Do not break on non int values in link.
2) [TASK] Remove support for legacy links.
3) [BUGFIX] Bugfix for edge cases with link creation.
4) [FEATURE] Add Hugo page view buttons into page context-sensitive menu.
5) [FEATURE] Implement support of content slide functionality.
6) [TASK] Implement support for links with fragments and legacy links to the current page.
7) [FEATURE] push export action to queue and execute its asynchronously.
8) [TASK] Services return ServiceResult object.
9) [TASK] Implement RTE fields parser for records indexer.
10) [BUGFIX] Add missing hugo build after removing hugo build from hugo export.
11) [BUGFIX] SimpleQueue count() method had no return.

0.5.0
~~~~~
1) [FEATURE] Add pid to content exported data.
2) [BUGFIX] PageIndexer - resolve the problem with elements from parent pages.

0.4.0
~~~~~
1) [TASK] Optimize getSlugifiedRootline / add two more methods for slugified rootline: getSlugifiedRootlineForUrl
2) [TASK] Implement support for `link.absRefPrefix` into file, folder and page link builders
3) [TASK] Cleanup - Remove testing line for preg_match
4) [TASK] refactor service classes
5) [TASK] Handle undelete of the content element action
6) [BUGFIX] Fix indexing of translated content elements
7) [TASK] refactor class Typo3PageRepository; PageIndexer - add the content elements from parent
8) [FEATURE] Implement grid content element exporter
9) [TASK] Implement multilang support into link builders
10) [TASK] Code cleanup / PSR-2 / phpdocs
11) [BUGFIX] Remove linkText form generated url make htmlspecialchars for title

0.3.0
~~~~~
1) [TASK] Optimize getSlugifiedRootline / add two more methods for slugified rootline: getSlugifiedRootlineForUrl
    getSlugifiedRootlineForFilePath
2) [TASK] Optimize PageLinkBuilder to use SourceBroker\Hugo\Typolink. Do nto try to make link for deleted page.
3) [BUGFIX] Normalize usage of languageUid vs sysLanguageUid in RootlineUtility class.
4) [TASK] Clean up the code after stopping passing of the configurator to content element via constructor.
5) [TASK] Move hugo link configuration to outside from "content" namespace
6) [TASK] Refactor configurator and use cached instances according to PID to avoid reading Page TSconfig on every construction.
7) [TASK] Implement RTE service and change typolinks into correct FE links when exporting bodytext field of text content element
8) [BUGFIX] Fix wrong full tag link text generation.
9) [BUGFIX] Fix wrong homepage link generation.

0.2.0
~~~~~~
1) [FEATURE] Extend sys_fie_reference export in DCE with standard link field.
2) [FEATURE] Add new backend and ext icon.
3) [FEATURE] Add Hugo build service and cli build task.
4) [TASK] Change module name.
5) [FEATURE] Show generated Hugo FrontMatter in Pages and Content editing forms.
6) [FEATURE] Do not create link if page is hidden.
7) [FEATURE] Add content element uid as default export field.

0.1.0
~~~~~~
1) [DOC] Update docs.
2) [FEATURE] Implement the scheduler tasks.
3) [FEATURE] add new content element: html.
4) [FEATURE] Make support for more that one image in DCE element
5) [TASK] Rename the name of command controller; rename the services.
6) [FEATURE] Return metadata for images in DCE element.
7) [FEATURE] Run media sync after every file processing.
8) [FEATURE] Use getRecordOverlay function to get translated content.
9) [FEATURE] Service for generating links based on typolink configuration.
10) [FEATURE] Create Field Transformer Class to modify content fields.
11) [FEATURE] Implement records indexer properties mapper.
12) [BUGFIX] convertTypolinkToLinkArray can return array of bool.
13) [FEATURE] Implement very basic version of Hugo Control Center BE module
14) [BUGFIX] Fix not sufficient check for fieldIsLink() in DCE exporter.
15) [TASK] Add link converter also for non section links.
16) [TASK] Add uid to image record instead of key value.
17) [TASK] Refactor support for links. Add initial support for all TYPO3 linktypes. [TODO - remove need of TSFE]
18) [TASK] Refactor getCommonContentElementData()

0.0.19
~~~~~~
1) [BUGFIX] Fix multilang menu taking folders and shortcuts to path.
2) [BUGFIX] Typo in function name.
3) [TASK] Disable menu generation for Hugo page below hidden page in TYPO3.

0.0.18
~~~~~~
1) [BUGFIX] Add cast to array if option is not set.

0.0.17
~~~~~~

1) [FEATURE] Implement way to put custom fields into h  ugo document.
2) [FEATURE] Implement init version for page translations. For media only a copy of indexes.
3) [FEATURE] Implement way to change how TYPO3 backend_layout name is transformed to Hugo so different filenames (case
sensitivity, dashes etc) can be used on both TYPo3 and Hugo.

0.0.16
~~~~~~

1) [TASK] Remove not longer needed "page" table override for menu (tx_hugo_menuid)

0.0.15
~~~~~~

1) [TASK] [!!!BREAKING] Change namesppace from indexer.records.exporter to page.indexer.records.exporter
2) [TASK] Protect have ing empty $hugoConfig->getOption('page.indexer.records.exporter')
3) [TASK] Remove unneeded fields from Documents class.
4) [TASK] Implement new way to generate menu data based on settings in TSConfig.

0.0.14
~~~~~~

1) [TASK] Refactor slot dispatcher to have only one method to collect documents instead of single Document and DocumentCollection.
2) [TASK] Remove single Document and move all into DocumentCollection.
3) [TASK] Make Traverser class decide about path to store files and not read that from Document.
4) [TASK] Make Document class to decide about filename instead of Writer class.

0.0.13
~~~~~~

1) [FEATURE] Add "parent" property to menus.
2) [FEATURE] Extend DataHanlder to react on tt_content changes.
3) [TASK] Extend the way backend_layout / backend_layout_next_level is choosen. (pull request #1 from netfarma)
4) [TASK] Simplify class mapper for DCE content elements.
5) [FEATURE] Add id, pid, weight to Hugo frontmatter to have ability to more easily query for subpages and pages.
6) [FEATURE] Prepare class for implementing exporter for gridelements CE.


0.0.12
~~~~~~

1) [FEATURE] Add special "warning" content element to pass some info from exporter.

0.0.11
~~~~~~

1) [FEATURE] Add info about content elements in each column.

0.0.10
~~~~~~

1) [BUGFIX] Add missing wrappers '---' for yaml -media files.
2) [FEATURE] Add symlink for storage fodler to hugo media folder.

0.0.9
~~~~~

1) [BUGFIX] Leave site roots foreach after first hugo enabled site root because content elements are the same for all
    root sites.
2) [FEATURE] Initial implementation for media export.
3) [FEATURE] Add possibility to overwrite DCE elements default CType namings in hugo export to have more meaning
   in partial namings.

0.0.8
~~~~~

1) [BUGFIX] Create directory for data/content if not exists yet.
2) [BUGFIX] Add missing TsConfig for content elements exporter.

0.0.7
~~~~~
1) [TASK] Move getSiteRootPages() from service class to Typo3PageRepository.
   Replace GeneralUtility::makeInstance with ObjectManager->get()
2) [TASK] Refactor for better naming for future content / media exporters.
3) [FEATURE] Init version for content element exporter. So far it works only from
    cli level and for header / text and dce content elements.

0.0.6
~~~~~
1) [FEATURE] Run hugo build after export tree finish. Init verison to be improved.

0.0.5
~~~~~
1) [TASK] Rename PageTraverser to TreeTraverser
2) [TASK] Rename metaData to frontMatter in Document class.
3) [TASK] Add support to disable tree export - its possible now to now export for some site root trees.
4) [TASK] Rename values of hugo menu identifiers in TYPO3.
5) [TASK] Add simple support for menu (assign menu, menu identifier, add weight)
6) [TASK] Make YamlWriter->clean() to be more safe in case wrong set of path to store.
7) [TASK] Exclude media folder from cleaning by Writer calss.
8) [FEATURE] Add DataHanler support for events in TYPO3 like add / delete / move page to regenerate content.

0.0.4
~~~~~
1) [BUGFIX] Fix lacking YAML "---" separator in md file / fix wrong extension for yaml writer.
2) [TASK] Remove auto finding for root page in Configurator / refactor Configurator class.
3) [FEATURE] Introduce support for multi site root. Each of site root should have own configuration of writer pathes
    to export content to separate folders.
4) [BUGFIX] Remove not needed ImageoptCommandController scheduler task init.
5) [TASK] Add folders to ignore after installing vendors.
6) [TASK] Add dummy "layout" value for having beginning working solution.

0.0.3
~~~~~
1) [TASK] Add cocur/slugify dependency.

0.0.2
~~~~~
1) [TASK] Add composer.json file.
2) [DOCS] Docs fixes.

0.0.1
~~~~~
1) Init version.

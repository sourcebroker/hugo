<?php

namespace SourceBroker\Hugo\Tca;

use SourceBroker\Hugo\Configuration\Configurator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Pages
 */
class Pages
{
    /**
     * Returns Hugo Front Matter for given page
     * @param $data
     * @return string
     */
    public function getHugoFrontMatter($data)
    {
        $content = '';
        if (!empty($data['row']['uid'])) {
            $configurator = Configurator::getByPid((int)$data['row']['uid']);

            $sysLanguageUid = 0; // TODO - do support for multilang
            $slugifiedRootline = GeneralUtility::makeInstance(\SourceBroker\Hugo\Utility\RootlineUtility::class,
                $data['row']['uid'], $sysLanguageUid)->getSlugifiedRootlinePath();

            $filename = PATH_site . rtrim($configurator->getOption('writer.path.content'), '/') .
                '/' . $slugifiedRootline . '_index.md';

            if (file_exists($filename)) {
                $content = '<pre style="line-height: 0.85em">'
                    . nl2br(htmlspecialchars(trim(file_get_contents($filename), "-\n")))
                    . '</pre>';
            } else {
                $content = 'Cannot read file ' . $filename . '<br>There can be several reasons:'
                    . '<ul>'
                    . '<li>page itself is hidden,</li>'
                    . '<li>one of parent page is hidden,</li> '
                    . '<li>TYPO3 Hugo export failed.</li>'
                    . '</ul>';
            }
        }
        return $content;
    }
}

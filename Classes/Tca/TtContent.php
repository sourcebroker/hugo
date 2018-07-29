<?php

namespace SourceBroker\Hugo\Tca;

use SourceBroker\Hugo\Configuration\Configurator;

/**
 * Class Pages
 */
class TtContent
{
    /**
     * @param $data
     * @return string
     */
    public function getHugoFrontMatter($data)
    {
        $content = '';
        if (!empty($data['row']['uid'])) {
            $configurator = Configurator::getByPid((int)$data['row']['pid']);
            $filename = PATH_site . rtrim($configurator->getOption('writer.path.data'), '/') .
                '/' . $data['row']['uid'] . '.yaml';
            if (file_exists($filename)) {
                $content = '<pre style="line-height: 0.85em">'
                    . nl2br(htmlspecialchars(trim(file_get_contents($filename), "-\n")))
                    . '</pre>';
            } else {
                $content = 'Cannot read file ' . $filename . '<br>Reasons can be:'
                    . '<ul>'
                    . '<li>TYPO3 Hugo export failed for content elements.</li>'
                    . '</ul>';
            }
        }
        return $content;
    }
}
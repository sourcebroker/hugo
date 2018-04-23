<?php

/***************************************************************
 *  Copyright notice
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace SourceBroker\Hugo\Configuration;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Configurator Class
 */
class Configurator
{
    /**
     * Configuration of module set as array
     *
     * @var null|array
     */
    protected $config = null;


    /**
     * Configurator constructor.
     * @param null $config
     * @param $pageIdForConfigurator
     * @throws \Exception
     */
    public function __construct($config = null, $pageIdForConfigurator = null)
    {
        if ($config !== null) {
            $this->setConfig($config);
        } else {
            $this->init($pageIdForConfigurator);
        }
    }

    /**
     * @return array|null
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array|null $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * Return option from configuration array with support for nested comma separated notation as "option1.suboption"
     *
     * @param string $name Configuration
     * @param null $overwriteConfig
     * @return array|null|string
     */
    public function getOption($name = null, $overwriteConfig = null)
    {
        $config = null;
        if (is_string($name)) {
            $pieces = explode('.', $name);
            if ($pieces !== false) {
                if ($overwriteConfig === null) {
                    $config = $this->config;
                } else {
                    $config = $overwriteConfig;
                }
                foreach ($pieces as $piece) {
                    if (!is_array($config) || !array_key_exists($piece, $config)) {
                        return null;
                    }
                    $config = $config[$piece];
                }
            }
        }
        return $config;
    }


    /**
     * Init configurator wiht TSconfig settings from cli params or scheduler parameters.
     *
     * @param int $rootPageForTsConfig
     * @throws \Exception
     */
    public function init($rootPageForTsConfig = null)
    {
        if ($rootPageForTsConfig === null) {

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->select('*')->from('pages')->where(
                $queryBuilder->expr()->eq('pid', 0),
                $queryBuilder->expr()->eq('deleted', 0)
            );
            $rootPageForTsConfigRow = $queryBuilder->execute()->fetchAll();
            if ($rootPageForTsConfigRow !== null) {
                $rootPageForTsConfig = $rootPageForTsConfigRow[0]['uid'];
            }
        }
        if ($rootPageForTsConfig !== null) {
            $serviceConfig = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Service\\TypoScriptService')
                ->convertTypoScriptArrayToPlainArray(BackendUtility::getPagesTSconfig($rootPageForTsConfig));
            if (isset($serviceConfig['tx_hugo'])) {
                $this->setConfig($serviceConfig['tx_hugo']);
            } else {
                throw new \Exception('There is no TSconfig for tx_hugo in the root page id=' . $rootPageForTsConfig,
                    1501692752398);
            }
        } else {
            throw new \Exception('Can not detect the root page to generate page TSconfig.', 1501700792654);
        }
    }

}

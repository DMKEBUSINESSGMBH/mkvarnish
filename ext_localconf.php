<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2017 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

defined('TYPO3_MODE') || exit('Access denied.');

call_user_func(
    function () {
        $configurationUtility = new \DMK\Mkvarnish\Utility\Configuration();

        switch (TYPO3_MODE) {
            case 'FE':
                $typoScriptSetup =
                    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:mkvarnish/Configuration/TypoScript/setup.txt">';
                if ($configurationUtility->isSendCacheHeadersEnabled()) {
                    $typoScriptSetup .= LF.'config.sendCacheHeaders = 1';
                }
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
                    'varnish',
                    'setup',
                    $typoScriptSetup,
                    43
                );
                break;
        }

        if ($configurationUtility->isSendCacheHeadersEnabled()) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['varnish'] = [
                'backend' => 'DMK\Mkvarnish\Cache\VarnishBackend',
                'frontend' => 'TYPO3\CMS\Core\Cache\Frontend\VariableFrontend',
                'groups' => ['pages', 'all'],
            ];
        }
    }
);

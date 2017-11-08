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

defined('TYPO3_MODE') || die('Access denied.');


call_user_func(
    function () {
        global $TYPO3_CONF_VARS;

        switch (TYPO3_MODE) {
            case 'FE':
                // add static ts
                \tx_rnbase_util_Extensions::addTypoScript(
                    'varnish',
                    'setup',
                    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:mkvarnish/Configuration/TypoScript/setup.txt">',
                    43
                );
                // Hook to add the cache tags
                $TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting']['mkvarnish'] = 'DMK\\Mkvarnish\\Hook\\FrontendHook->handleHeaders';
                break;
            case 'BE':
                // Hook for clear cache
                $TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['mkvarnish'] = 'DMK\\Mkvarnish\\Hook\\DataHandlerHook->clearCachePostProc';
                break;
        }
    }
);


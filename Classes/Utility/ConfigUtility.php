<?php
namespace DMK\Mkvarnish\Utility;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3 Hook to extend the header with cache tags
 *
 * @package TYPO3
 * @subpackage DMK\Mkvarnish
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class ConfigUtility implements \Tx_Rnbase_Interface_Singleton
{
    /**
     * The extension configuration
     *
     * @var array
     */
    private $extConf = null;

    /**
     * Returns an instance of this config
     *
     * @return \DMK\Mkvarnish\Utility\ConfigUtility
     */
    public static function instance()
    {
        return \tx_rnbase::makeInstance(get_called_class());
    }

    /**
     * Gets a config value from extension configuration
     *
     * @param string $key
     *
     * @visibility private Only protected for Unittests
     *
     * @return mixed
     */
    protected function getExtConfValue($key)
    {
        if ($this->extConf === null) {
            $this->extConf = unserialize(
                $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mkvarnish']
            );
        }

        return isset($this->extConf[$key]) ? $this->extConf[$key] : null;
    }

    /**
     * Check if we should send some cache headers
     *
     * @return bool
     * */
    public function isSendCacheHeadersEnabled()
    {
        $forced = (int) self::getExtConfValue('sendCacheHeaders');
        switch ($forced) {
            case 1:
                return true;
            case 2:
                return false;
            case 0:
            default:
                return $this->isRevProxy();
        }
    }

    /**
     * Check if we are behind a reverse proxy
     *
     * @return bool
     * */
    public function isRevProxy()
    {
        return \Tx_Rnbase_Utility_T3General::getIndpEnv('TYPO3_REV_PROXY');
    }

    /**
     * Returns HMAC of the sitename
     *
     * @return mixed
     */
    public function getSitename()
    {
        return GeneralUtility::hmac($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
    }

    /**
     * Returns a list of hosts to purge
     *
     * @return array
     */
    public function getHostnames()
    {
        $hosts = \Tx_Rnbase_Utility_Strings::trimExplode(
            ',',
            self::getExtConfValue('hostnames'),
            true
        );
        $hosts[] = GeneralUtility::getIndpEnv('HTTP_HOST');

        return $hosts;
    }
}

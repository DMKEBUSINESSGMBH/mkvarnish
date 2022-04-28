<?php

namespace DMK\Mkvarnish\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

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

/**
 * TYPO3 Hook to extend the header with cache tags.
 *
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class Configuration implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * The extension configuration.
     *
     * @var array
     */
    private $extConf = null;

    /**
     * Gets a config value from extension configuration.
     *
     * @param string $key
     *
     * @visibility private Only protected for Unittests
     *
     * @return mixed
     */
    protected function getExtConfValue($key)
    {
        if (null === $this->extConf) {
            $this->extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['mkvarnish'];
        }

        return isset($this->extConf[$key]) ? $this->extConf[$key] : null;
    }

    /**
     * Check if we should send some cache headers.
     *
     * @return bool
     * */
    public function isSendCacheHeadersEnabled()
    {
        if ($this->isNotLiveWorkspace()) {
            return false;
        }

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

    protected function isNotLiveWorkspace(): bool
    {
        return isset($GLOBALS['BE_USER']->workspace) && 0 !== $GLOBALS['BE_USER']->workspace;
    }

    /**
     * Check if we are behind a reverse proxy.
     *
     * @return bool
     * */
    public function isRevProxy()
    {
        return GeneralUtility::getIndpEnv('TYPO3_REV_PROXY');
    }

    /**
     * Returns HMAC of the sitename.
     *
     * @return mixed
     */
    public function getHmacForSitename()
    {
        return GeneralUtility::hmac($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
    }

    /**
     * Returns a list of hosts to purge.
     *
     * @return array
     */
    public function getHostNamesForPurge()
    {
        $hosts = GeneralUtility::trimExplode(
            ',',
            self::getExtConfValue('hostnames'),
            true
        );
        if (empty($hosts)) {
            $hosts[] = GeneralUtility::getIndpEnv('HTTP_HOST');
        }

        return $hosts;
    }
}

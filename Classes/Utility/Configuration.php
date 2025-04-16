<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mkvarnish" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

namespace DMK\Mkvarnish\Utility;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\SingletonInterface;
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
class Configuration implements SingletonInterface
{
    /**
     * The extension configuration.
     *
     * @var array
     */
    private $extConf;

    /**
     * Gets a config value from extension configuration.
     *
     * @param string $key
     *
     * @visibility private Only protected for Unittests
     */
    protected function getExtConfValue($key)
    {
        if (null === $this->extConf) {
            $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('mkvarnish');
        }

        return $this->extConf[$key] ?? null;
    }

    /**
     * Check if we should send some cache headers.
     *
     * @return bool
     * */
    public function isSendCacheHeadersEnabled(): string|bool|array|null
    {
        $forced = (int) self::getExtConfValue('sendCacheHeaders');

        return match ($forced) {
            1 => true,
            2 => false,
            default => $this->isRevProxy(),
        };
    }

    /**
     * Check if we are behind a reverse proxy.
     *
     * @return bool
     * */
    public function isRevProxy(): string|bool|array|null
    {
        return GeneralUtility::getIndpEnv('TYPO3_REV_PROXY');
    }

    /**
     * Returns HMAC of the sitename.
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function getHmacForSitename(): string
    {
        if ((new Typo3Version())->getMajorVersion() < 13) {
            return GeneralUtility::hmac($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
        }

        return GeneralUtility::makeInstance(HashService::class)->hmac(
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
            'mkvarnish'
        );
    }

    /**
     * Returns a list of hosts to purge.
     */
    public function getHostNamesForPurge(): array
    {
        $hosts = GeneralUtility::trimExplode(
            ',',
            self::getExtConfValue('hostnames'),
            true
        );
        if ([] === $hosts) {
            $hosts[] = GeneralUtility::getIndpEnv('HTTP_HOST');
        }

        return $hosts;
    }
}

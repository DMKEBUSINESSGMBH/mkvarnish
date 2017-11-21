<?php

namespace DMK\Mkvarnish\Hook;

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

use DMK\Mkvarnish\Utility\ConfigUtility;
use DMK\Mkvarnish\Utility\CurlMultiUtility;

/**
 * TYPO3 Hook to extend the header with cache tags
 *
 * @package TYPO3
 * @subpackage DMK\Mkvarnish
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class DataHandlerHook
{
    /**
     * Clear cache hook
     *
     * @param array $params Parameter
     *
     * @return void
     */
    public function clearCachePostProc(array $params)
    {
        /*
         * either is 'cacheCmd'
         * or 'table', 'uid', 'uid_page'
         *
         * clear cache commands:
         * cacheCmd:system > roter blitz (system cache)
         * cacheCmd:all > gelber blitz (general caches)
         * cacheCmd:pages > grüner blitz (frontend caches)
         *
         * on tca edit:
         * table:pages, uid:419, uid_page:419 > edit a page
         * table:tt_content, uid:1701, uid_page:419 > edit tt_content on page
         * table:tt_news, uid:88, uid_page:3333 > edit tt_news element on storage
         */

        if (!$this->isSendCacheHeadersEnabled()) {
            return;
        }


        // if uid_page is -1, were in a draft workspace and skip Varnish clearing
        if (isset($params['uid_page']) && $params['uid_page'] == -1) {
            return;
        }

        $header = [
            // 'X-Cache-Tags' => '',
            // 'X-Varnish-Ban-All' => '0',
            'X-TYPO3-Sitename' => $this->getSitename(),
        ];

        if (isset($params['cacheCmd'])) {
            // purge the whole varnish cache
            $header['X-Varnish-Purge-All'] = '1';
        } else {
            // purge the varnich based on cache tags
            $cacheTags = [];
            $cacheTags[] = $params['table'];
            $cacheTags[] = $params['table'] . '_' . $params['uid'];
            $cacheTags[] = 'pageId_' . $params['uid_page'];

            $header['X-Cache-Tags'] = $this->convertCacheTagsForPurge($cacheTags);
        }

        $this->executePurge($header);
    }

    /**
     * Check if we are behind a reverse proxy
     *
     * @return bool
     * */
    protected function isSendCacheHeadersEnabled()
    {
        return \DMK\Mkvarnish\Utility\ConfigUtility::instance()->isSendCacheHeadersEnabled();
    }

    /**
     * Creates the PURGE requests
     *
     * @param array $options
     *
     * @return void
     */
    protected function executePurge(array $options)
    {
        // map the header options
        $headers = [];
        foreach ($options as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }

        $method = 'PURGE';

        $varnishHttp = CurlMultiUtility::instance();
        foreach (ConfigUtility::instance()->getHostnames() as $hostname) {
            $varnishHttp->addCommand($method, $hostname, $headers);
        }
    }

    /**
     * Escapes the tags and creates the regex
     *
     * @param array $tags
     *
     * @return array
     */
    protected function convertCacheTagsForPurge(array $tags)
    {
        $escapedTags = array_map('preg_quote', $tags);

        return sprintf('(%s)(,.+)?$', implode('|', $escapedTags));
    }

    /**
     * Returns HMAC of the sitename
     *
     * @return mixed
     */
    protected function getSitename()
    {
        return \DMK\Mkvarnish\Utility\ConfigUtility::instance()->getSitename();
    }
}

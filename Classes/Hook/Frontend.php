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

/**
 * TYPO3 Hook to extend the header with cache tags
 *
 * @package TYPO3
 * @subpackage DMK\Mkvarnish
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class Frontend
{
    /**
     * ContentPostProc-output hook to add cache headers.
     *
     * @return void
     */
    public function handleHeaders()
    {
        $headers = $this->getHeaders();

        if (!empty($headers)) {
            $this->sendHeaders($headers);
        }
    }

    /**
     * Builds the Varnish headers
     *
     * @return array
     */
    protected function getHeaders()
    {
        $headers = [];

        if ($this->isSendCacheHeadersEnabled()) {
            $headers = $this->getHeadersForVarnish();
        }

        return $headers;
    }

    /**
     * @return array
     */
    protected function getHeadersForVarnish()
    {
        $tsfe = $this->getTsFe();

        $headers = $this->getHeadersForCacheTags();
        $headers['X-TYPO3-Sitename'] = $this->getHmacForSitename();
        /// developer infos only. this headers should be removed in varnich vcl
        $headers['X-TYPO3-cHash'] = $tsfe->newHash ?: $tsfe->cHash;
        $headers['X-TYPO3-INTincScripts'] = count($tsfe->config['INTincScript']);

        return $headers;
    }

    /**
     * Send the Varnish headers
     *
     * @param array $headers
     *
     * @return void
     */
    protected function sendHeaders(
        array $headers
    ) {
        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }
    }

    /**
     * Reads the cache tags from the typoscript frontend conroller
     *
     * @return array
     */
    protected function getHeadersForCacheTags()
    {
        $tsfe = $this->getTsFe();

        // the cache tags are protected, but we need these tags for purge later
        $reflection = new \ReflectionClass($tsfe);
        $property = $reflection->getProperty('pageCacheTags');
        $property->setAccessible(true);

        $cacheTags = $property->getValue($tsfe);

        // if we have no cache tags this means that the page is requested
        // for the second time and is now taken from the TYPO3 page cache as
        // TYPO3 sets the pageCacheTags only when the page is cached initially.
        // This also means that varnish was not able to cache the first request.
        // Othwerwise we wouldn't be here. Possible reasons could be that some USER plugin triggered
        // the creation of the fe_typo_user cookie for example by writing session data.
        // As the plugin is cached the fe_typo_user wouldn't be written for subsequent
        // requests of other users. This would cause varnish to cache the second request
        // (cached version of TYPO3). This is problematic as the actual cache tags
        // of this page are missing. The page would be cached by varnish without correct cache
        // tags. When the cache is cleared in the BE by cache tags we wouldn't clear the correct
        // pages in varnish because of wrong cache tags. That's why it's better to disable caching
        // by varnish so the problem can be investigated.
        if (empty($cacheTags)) {
            $headers['Cache-control'] = 'private, no-store';
        } else {
            $headers['X-Cache-Tags'] = implode(',', $cacheTags);
        }

        return $headers;
    }

    /**
     * Returns HMAC of the sitename
     *
     * @return mixed
     */
    protected function getHmacForSitename()
    {
        $configurationUtility = new \DMK\Mkvarnish\Utility\Configuration();

        return $configurationUtility->getHmacForSitename();
    }

    /**
     * Check if we are behind a reverse proxy
     *
     * @return bool
     * */
    protected function isSendCacheHeadersEnabled()
    {
        $configurationUtility = new \DMK\Mkvarnish\Utility\Configuration();

        return $configurationUtility->isSendCacheHeadersEnabled();
    }

    /**
     * An short alias to get the typoscript frontend conroller
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTsFe()
    {
        return \tx_rnbase_util_TYPO3::getTSFE();
    }
}

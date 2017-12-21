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
class FrontendHook
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
        $headers['X-Cache-Tags'] = implode(',', $this->getCacheTags());
        $headers['X-TYPO3-Sitename'] = $this->getSitename();
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
    protected function getCacheTags()
    {
        $tsfe = $this->getTsFe();

        // the cache tags are protected, but we need these tags for purge later
        $reflection = new \ReflectionClass($tsfe);
        $property = $reflection->getProperty('pageCacheTags');
        $property->setAccessible(true);

        $cacheTags = $property->getValue($tsfe);

        if (empty($cacheTags)) {
            $cacheTags = ['pages', 'pageId_' . $tsfe->id];
        }

        return $cacheTags;
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
     * An short alias to get the typoscript frontend conroller
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTsFe()
    {
        return \tx_rnbase_util_TYPO3::getTSFE();
    }
}

<?php

namespace DMK\Mkvarnish\Middleware;

use DMK\Mkvarnish\Repository\CacheTagsRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 *  Copyright notice.
 *
 *  (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
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
 */

/**
 * TYPO3 Middleware to extend the header with cache tags.
 *
 * @author Philipp Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class SetVarnishHeadersMiddleware implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $headers = $this->getHeaders();

        if (!empty($headers)) {
            $response = $this->addHeadersToResponse($response, $headers);
        }

        return $response;
    }

    /**
     * Builds the Varnish headers.
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
     * Check if we are behind a reverse proxy.
     *
     * @return bool
     * */
    protected function isSendCacheHeadersEnabled()
    {
        $configurationUtility = new \DMK\Mkvarnish\Utility\Configuration();

        return $configurationUtility->isSendCacheHeadersEnabled();
    }

    /**
     * @return array
     */
    protected function getHeadersForVarnish()
    {
        $tsfe = $this->getTsFe();

        $headers = $this->getHeadersForCacheTags();
        // this header is essential and used in varnish configuration
        $headers['X-TYPO3-Sitename'] = $this->getHmacForSitename();
        // / developer infos only. this headers should be removed in varnich vcl
        $headers['X-TYPO3-cHash'] = $this->getCurrentCacheHash();

        if (array_key_exists('INTincScript', $tsfe->config)) {
            $headers['X-TYPO3-INTincScripts'] = count((array) $tsfe->config['INTincScript']);
        }

        return $headers;
    }

    /**
     * Reads the cache tags from the typoscript frontend conroller.
     *
     * @return array
     */
    protected function getHeadersForCacheTags()
    {
        $tsfe = $this->getTsFe();

        $cacheTags = array_unique($tsfe->getPageCacheTags());

        // When the page content is delivered from the TYPO3 cache the
        // cache tags won't be present anymore. That's why we save them
        // so we can restore them even when TYPO3 delivers content from cache.
        // Otherwise Varnish would only be able to cache properly
        // if the first request to the page is cacheable. If for example
        // a logged in FE user makes the first request, the page is not
        // cacheable by Varnish. On subsequent requests the page would
        // still not be cacheable because of missing cache tags.
        if (empty($cacheTags)) {
            $cacheTags = $this->getCacheTagsByCacheHash($this->getCurrentCacheHash());
        } else {
            $this->saveCacheTagsByCacheHash($cacheTags, $this->getCurrentCacheHash());
        }
        $headers['X-Cache-Tags'] = implode(',', $cacheTags);

        return $headers;
    }

    /**
     * An short alias to get the typoscript frontend conroller.
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTsFe()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @param array $cacheTags
     * @param string $cacheHash
     *
     * @return void
     */
    protected function saveCacheTagsByCacheHash(array $cacheTags, $cacheHash)
    {
        $cacheTagsRepository = $this->getCacheTagsRepository();

        $cacheTagsRepository->deleteByCacheHash($cacheHash);

        foreach ($cacheTags as $cacheTag) {
            $cacheTagsRepository->insertByTagAndCacheHash($cacheTag, $cacheHash);
        }
    }

    /**
     * @param string $cacheHash
     *
     * @return array
     */
    protected function getCacheTagsByCacheHash($cacheHash)
    {
        $cacheTags = [];
        $cacheTagsDatabaseEntries = $this->getCacheTagsRepository()->getByCacheHash($cacheHash);

        foreach ($cacheTagsDatabaseEntries as $cacheTagsDatabaseEntry) {
            $cacheTags[] = $cacheTagsDatabaseEntry['tag'];
        }

        return $cacheTags;
    }

    /**
     * @return \DMK\Mkvarnish\Repository\CacheTagsRepository
     */
    protected function getCacheTagsRepository()
    {
        return new CacheTagsRepository();
    }

    /**
     * Returns HMAC of the sitename.
     *
     * @return mixed
     */
    protected function getHmacForSitename()
    {
        $configurationUtility = new \DMK\Mkvarnish\Utility\Configuration();

        return $configurationUtility->getHmacForSitename();
    }

    /**
     * @return string
     */
    protected function getCurrentCacheHash()
    {
        $typoscriptFrontendController = $this->getTsFe();

        return $typoscriptFrontendController->newHash ?: $typoscriptFrontendController->cHash;
    }

    /**
     * Send the Varnish headers.
     *
     * @param array $headers
     *
     * @return void
     */
    protected function addHeadersToResponse(
        ResponseInterface $response,
        array $headers
    ): ResponseInterface {
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}

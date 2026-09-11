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

namespace DMK\Mkvarnish\Cache;

use DMK\Mkvarnish\Repository\CacheTagsRepository;
use DMK\Mkvarnish\Utility\Configuration;
use DMK\Mkvarnish\Utility\CurlQueue;

/***************************************************************
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
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
 * Varnish takes care itself what is put into cache and what is read from cache.
 * This backend only provides functions to clear the cache in varnish completely
 * or by tags. Nothing more, nothing less.
 *
 * DMK\Mkvarnish\Cache$Backend
 *
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class VarnishBackend extends \TYPO3\CMS\Core\Cache\Backend\AbstractBackend implements \TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface
{
    /**
     * @see \TYPO3\CMS\Core\Cache\Backend\BackendInterface::set()
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null): void
    {
        $this->throwExceptionIfNotImplemented();
    }

    /**
     * @throws \Exception
     */
    protected function throwExceptionIfNotImplemented(): never
    {
        throw new \Exception('the varnish cache backend can only remove cache entries by tags or the complete cache at the moment');
    }

    /**
     * @see \TYPO3\CMS\Core\Cache\Backend\BackendInterface::get()
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function get($entryIdentifier): void
    {
        $this->throwExceptionIfNotImplemented();
    }

    /**
     * @see \TYPO3\CMS\Core\Cache\Backend\BackendInterface::has()
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function has($entryIdentifier): bool
    {
        $this->throwExceptionIfNotImplemented();
    }

    /**
     * @see \TYPO3\CMS\Core\Cache\Backend\BackendInterface::remove()
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function remove($entryIdentifier): bool
    {
        $this->throwExceptionIfNotImplemented();
    }

    /**
     * @see \TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface::findIdentifiersByTag()
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function findIdentifiersByTag($tag): array
    {
        $this->throwExceptionIfNotImplemented();
    }

    /**
     * @see \TYPO3\CMS\Core\Cache\Backend\BackendInterface::flush()
     */
    public function flush(): void
    {
        if ($this->getConfigurationUtility()->isSendCacheHeadersEnabled()) {
            $this->executePurge(['X-Varnish-Purge-All' => 1]);
            $this->truncateCacheTagsTable();
        }
    }

    /**
     * @see \TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface::flushByTag()
     */
    public function flushByTag($tag): void
    {
        if ($this->getConfigurationUtility()->isSendCacheHeadersEnabled()) {
            $this->executePurge(['X-Cache-Tags' => $this->convertCacheTagForPurge($tag)]);
            $this->deleteFromCacheTagsTableByTag($tag);
        }
    }

    /**
     * Escapes the tag and creates the regex to match it against the (compressed)
     * X-Cache-Tags header set in \DMK\Mkvarnish\Utility\Headers.
     *
     * Record specific tags (e.g. tt_content_5) are stored compressed as
     * "tt_content{,…,5,…,}", so the uid has to be matched within its table group.
     * Table wide or custom tags (e.g. tt_content, pages) are matched either as a
     * standalone token or as the prefix of such a compressed group.
     *
     * @param string $tag
     */
    protected function convertCacheTagForPurge($tag): string
    {
        if (1 === preg_match('/^([a-z0-9_]+)_(\d+)$/i', (string) $tag, $matches)) {
            return '(^|,)'.preg_quote($matches[1]).'\{[^}]*,'.preg_quote($matches[2]).',';
        }

        return '(^|,)'.preg_quote((string) $tag).'(\{|,|$)';
    }

    /**
     * @return void
     */
    protected function executePurge(array $headers)
    {
        $headers['X-TYPO3-Sitename'] = $this->getHmacForSitename();
        $headersForCurl = [];
        foreach ($headers as $key => $value) {
            $headersForCurl[] = $key.': '.$value;
        }

        $method = 'PURGE';

        $curlQueueUtility = $this->getCurlQueueUtility();
        foreach ($this->getHostNamesForPurge() as $hostname) {
            $curlQueueUtility->addCommand($method, $hostname, $headersForCurl);
        }
    }

    protected function getHmacForSitename(): string
    {
        return $this->getConfigurationUtility()->getHmacForSitename();
    }

    protected function getCurlQueueUtility(): CurlQueue
    {
        return new CurlQueue();
    }

    protected function getHostNamesForPurge(): array
    {
        return $this->getConfigurationUtility()->getHostNamesForPurge();
    }

    protected function getConfigurationUtility(): Configuration
    {
        return new Configuration();
    }

    /**
     * @return void
     */
    protected function truncateCacheTagsTable()
    {
        $this->getCacheTagsRepository()->truncateTable();
    }

    /**
     * @return void
     */
    protected function deleteFromCacheTagsTableByTag(string $tag)
    {
        $this->getCacheTagsRepository()->deleteByTag($tag);
    }

    protected function getCacheTagsRepository(): CacheTagsRepository
    {
        return new CacheTagsRepository();
    }

    /**
     * @see \TYPO3\CMS\Core\Cache\Backend\BackendInterface::collectGarbage()
     */
    public function collectGarbage(): void
    {
        // varnish handles garbage collection itself
    }
}

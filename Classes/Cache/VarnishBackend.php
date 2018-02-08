<?php
namespace DMK\Mkvarnish\Cache;

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
 * @package         TYPO3
 * @subpackage
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class VarnishBackend
    extends \TYPO3\CMS\Core\Cache\Backend\AbstractBackend
    implements \TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface
{

    /**
     * {@inheritDoc}
     * @see \TYPO3\CMS\Core\Cache\Backend\BackendInterface::set()
     */
    public function set($entryIdentifier, $data, array $tags = array(), $lifetime = null)
    {
        $this->throwExceptionIfNotImplemented();
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function throwExceptionIfNotImplemented()
    {
        throw new \Exception(
            'the varnish cache backend can only remove cache entries by tags or the complete cache at the moment'
        );
    }

    /**
     * {@inheritDoc}
     * @see \TYPO3\CMS\Core\Cache\Backend\BackendInterface::get()
     */
    public function get($entryIdentifier)
    {
        $this->throwExceptionIfNotImplemented();
    }

    /**
     * {@inheritDoc}
     * @see \TYPO3\CMS\Core\Cache\Backend\BackendInterface::has()
     */
    public function has($entryIdentifier)
    {
        $this->throwExceptionIfNotImplemented();
    }

    /**
     * {@inheritDoc}
     * @see \TYPO3\CMS\Core\Cache\Backend\BackendInterface::remove()
     */
    public function remove($entryIdentifier)
    {
        $this->throwExceptionIfNotImplemented();
    }

    /**
     * {@inheritDoc}
     * @see \TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface::findIdentifiersByTag()
     */
    public function findIdentifiersByTag($tag)
    {
        $this->throwExceptionIfNotImplemented();
    }

    /**
     * {@inheritDoc}
     * @see \TYPO3\CMS\Core\Cache\Backend\BackendInterface::flush()
     */
    public function flush()
    {
        $this->executePurge(['X-Varnish-Purge-All' => 1]);
    }

    /**
     * {@inheritDoc}
     * @see \TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface::flushByTag()
     */
    public function flushByTag($tag)
    {
        $this->executePurge(['X-Cache-Tags' => $this->convertCacheTagForPurge($tag)]);
    }

    /**
     * Escapes the tag and creates the regex
     *
     * @param string $tag
     *
     * @return string
     */
    protected function convertCacheTagForPurge($tag)
    {
        $escapedTag = array_map('preg_quote', [$tag]);

        return sprintf('(%s)(,.+)?$', implode('|', $escapedTag));
    }

    /**
     * @param array $headers
     *
     * @return void
     */
    protected function executePurge(array $headers)
    {
        $headers['X-TYPO3-Sitename'] = $this->getHmacForSitename();
        $headersForCurl = [];
        foreach ($headers as $key => $value) {
            $headersForCurl[] = $key . ': ' . $value;
        }

        $method = 'PURGE';

        $curlQueueUtility = $this->getCurlQueueUtility();
        foreach ($this->getHostNamesForPurge() as $hostname) {
            $curlQueueUtility->addCommand($method, $hostname, $headersForCurl);
        }
    }

    /**
     * @return mixed|string
     */
    protected function getHmacForSitename()
    {
        $configurationUtility = new Configuration();

        return $configurationUtility->getHmacForSitename();
    }

    /**
     * @return \DMK\Mkvarnish\Utility\CurlQueue
     */
    protected function getCurlQueueUtility()
    {
        return new CurlQueue();
    }

    /**
     * @return array
     */
    protected function getHostNamesForPurge()
    {
        $configurationUtility = new Configuration();

        return $configurationUtility->getHostNamesForPurge();
    }

    /**
     * {@inheritDoc}
     * @see \TYPO3\CMS\Core\Cache\Backend\BackendInterface::collectGarbage()
     */
    public function collectGarbage()
    {
        // varnish handles garbage collection itself
    }
}

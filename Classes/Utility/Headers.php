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

use DMK\Mkvarnish\Repository\CacheTagsRepository;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

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
 * Utility to get the headers for varnish.
 *
 * @author Michael Wagner
 * @author Philipp Wagner
 * @author Hannes Bochmann
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class Headers
{
    public function __construct(
        protected CacheTagsRepository $cacheTagsRepository,
        protected Configuration $configuration,
    ) {
    }

    public function get(): array
    {
        if ($this->configuration->isSendCacheHeadersEnabled() && $this->isLiveWorkspace()) {
            return $this->getHeadersForVarnish();
        }

        return [];
    }

    /**
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    protected function isLiveWorkspace(): bool
    {
        return ($GLOBALS['BE_USER']->workspace ?? 0) == 0;
    }

    protected function getHeadersForVarnish(): array
    {
        $tsfe = $this->getTsFe();

        $headers = $this->getHeadersForCacheTags();
        // this header is essential and used in varnish configuration
        $headers['X-TYPO3-Sitename'] = $this->getHmacForSitename();
        // developer infos only. this headers should be removed in varnich vcl
        $headers['X-TYPO3-cHash'] = $this->getCurrentCacheHash();
        $headers['X-TYPO3-INTincScripts'] = count((array) ($tsfe->config['INTincScript'] ?? []));

        return $headers;
    }

    /**
     * @SuppressWarnings("PHPMD.ElseExpression")
     */
    protected function getHeadersForCacheTags(): array
    {
        $cacheTags = array_unique($this->getPageCacheTags());

        // When the page content is delivered from the TYPO3 cache the
        // cache tags won't be present anymore. That's why we save them
        // so we can restore them even when TYPO3 delivers content from cache.
        // Otherwise Varnish would only be able to cache properly
        // if the first request to the page is cacheable. If for example
        // a logged in FE user makes the first request, the page is not
        // cacheable by Varnish. On subsequent requests the page would
        // still not be cacheable because of missing cache tags.
        if ([] === $cacheTags) {
            $cacheTags = $this->getCacheTagsByCacheHash($this->getCurrentCacheHash());
        } else {
            $this->saveCacheTagsByCacheHash($cacheTags, $this->getCurrentCacheHash());
        }

        $headers['X-Cache-Tags'] = implode(',', $cacheTags);

        return $headers;
    }

    /**
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    protected function getPageCacheTags(): array
    {
        if ((new Typo3Version())->getMajorVersion() < 13) {
            return $this->getTsFe()->getPageCacheTags();
        }

        return array_map(
            fn (CacheTag $cacheTag): string => $cacheTag->name,
            $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.cache.collector')->getCacheTags()
        );
    }

    /**
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    protected function getTsFe(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    protected function saveCacheTagsByCacheHash(array $cacheTags, string $cacheHash): void
    {
        $this->cacheTagsRepository->deleteByCacheHash($cacheHash);

        foreach ($cacheTags as $cacheTag) {
            $this->cacheTagsRepository->insertByTagAndCacheHash($cacheTag, $cacheHash);
        }
    }

    protected function getCacheTagsByCacheHash(string $cacheHash): array
    {
        $cacheTags = [];
        $cacheTagsDatabaseEntries = $this->cacheTagsRepository->getByCacheHash($cacheHash);

        foreach ($cacheTagsDatabaseEntries as $cacheTagsDatabaseEntry) {
            $cacheTags[] = $cacheTagsDatabaseEntry['tag'];
        }

        return $cacheTags;
    }

    protected function getHmacForSitename(): string
    {
        $configurationUtility = new Configuration();

        return $configurationUtility->getHmacForSitename();
    }

    protected function getCurrentCacheHash(): string
    {
        $typoscriptFrontendController = $this->getTsFe();

        return '' !== $typoscriptFrontendController->newHash && '0' !== $typoscriptFrontendController->newHash
            ? $typoscriptFrontendController->newHash
            : $this->getRequest()->getAttribute('routing')->get('cHash');
    }

    /**
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    private function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}

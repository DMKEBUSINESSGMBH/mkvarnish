<?php

namespace DMK\Mkvarnish\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
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
 * DMK\Mkvarnish\Repository$CacheTags.
 *
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class CacheTagsRepository
{
    /**
     * @var string
     */
    public const TABLE_NAME = 'tx_mkvarnish_cache_tags';

    public function insertByTagAndCacheHash(string $tag, string $cacheHash): void
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->insert(self::TABLE_NAME)
            ->values([
                'tag' => $tag,
                'cache_hash' => $cacheHash,
            ])
            ->executeStatement  ();
    }

    public function getByCacheHash(string $cacheHash): \Traversable
    {
        $queryBuilder = $this->getQueryBuilder();

        return $queryBuilder->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('cache_hash', $queryBuilder->createNamedParameter($cacheHash))
            )
            ->executeQuery()
            ->iterateAssociative();
    }

    public function deleteByCacheHash(string $cacheHash): void
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->delete(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('cache_hash', $queryBuilder->createNamedParameter($cacheHash))
            )
            ->executeStatement();
    }

    /**
     * @return void
     */
    public function truncateTable()
    {
        $this->getQueryBuilder()->getConnection()->truncate(self::TABLE_NAME);
    }

    /**
     * there can be cache hashes with more than one cache tag associated. When deleting
     * by a cache tag it makes no sense to keep other tags associated with the cache hashes
     * associated to the given tag.
     */
    public function deleteByTag(string $tag): void
    {
        foreach ($this->getByTag($tag) as $row) {
            $this->deleteByCacheHash($row['cache_hash']);
        }
    }

    public function getByTag(string $tag): \Traversable
    {
        $queryBuilder = $this->getQueryBuilder();

        return $queryBuilder->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('tag', $queryBuilder->createNamedParameter($tag))
            )
            ->executeQuery()
            ->iterateAssociative();
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder;
    }
}

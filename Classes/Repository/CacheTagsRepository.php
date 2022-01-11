<?php

namespace DMK\Mkvarnish\Repository;

use Sys25\RnBase\Database\Connection;

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
    const TABLE_NAME = 'tx_mkvarnish_cache_tags';

    /**
     * @param string $tag
     * @param string $cacheHash
     *
     * @return void
     */
    public function insertByTagAndCacheHash($tag, $cacheHash)
    {
        $this->getDatabaseUtility()->doInsert(
            self::TABLE_NAME,
            [
                'tag' => $tag,
                'cache_hash' => $cacheHash,
            ]
        );
    }

    /**
     * @param string $cacheHash
     *
     * @return array
     */
    public function getByCacheHash($cacheHash)
    {
        $databaseUtility = $this->getDatabaseUtility();

        return $databaseUtility->doSelect(
            '*',
            self::TABLE_NAME,
            [
                'where' => 'cache_hash = '.$databaseUtility->fullQuoteStr($cacheHash),
                'enablefieldsoff' => true,
            ]
        );
    }

    /**
     * @param string $cacheHash
     *
     * @return void
     */
    public function deleteByCacheHash($cacheHash)
    {
        $databaseUtility = $this->getDatabaseUtility();
        $databaseUtility->doDelete(
            self::TABLE_NAME,
            'cache_hash = '.$databaseUtility->fullQuoteStr($cacheHash)
        );
    }

    /**
     * @return void
     */
    public function truncateTable()
    {
        $this->getDatabaseUtility()->doQuery('TRUNCATE '.self::TABLE_NAME);
    }

    /**
     * there can be cache hashes with more than one cache tag associated. When deleting
     * by a cache tag it makes no sense to keep other tags associated with the cache hashes
     * associated to the given tag.
     *
     * @param string $tag
     *
     * @return void
     */
    public function deleteByTag($tag)
    {
        foreach ($this->getByTag($tag) as $row) {
            $this->deleteByCacheHash($row['cache_hash']);
        }
    }

    /**
     * @param string $tag
     *
     * @return array
     */
    public function getByTag($tag)
    {
        $databaseUtility = $this->getDatabaseUtility();

        return $databaseUtility->doSelect(
            '*',
            self::TABLE_NAME,
            [
                'where' => 'tag = '.$databaseUtility->fullQuoteStr($tag),
                'enablefieldsoff' => true,
            ]
        );
    }

    /**
     * @return Connection
     */
    protected function getDatabaseUtility()
    {
        return Connection::getInstance();
    }
}

<?php
namespace DMK\Mkvarnish\Tests\Unit\Repository;

use DMK\Mkvarnish\Repository\CacheTagsRepository;

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
 * DMK\Mkvarnish\Tests\Unit\Hooks$CacheTagsRepositoryTest
 *
 * @package         TYPO3
 * @subpackage      mkvarnish
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class CacheTagsRepositoryTest extends \tx_rnbase_tests_BaseTestCase
{

    /**
     * @group unit
     */
    public function testGetDatabaseUtility()
    {
        self::assertInstanceOf(
            'Tx_Rnbase_Database_Connection',
            $this->callInaccessibleMethod(new CacheTagsRepository(), 'getDatabaseUtility')
        );
    }

    /**
     * @group unit
     */
    public function testInsertByTagAndCacheHash()
    {
        $databaseUtility = $this->getMock('Tx_Rnbase_Database_Connection', ['doInsert']);
        $databaseUtility
            ->expects(self::once())
            ->method('doInsert')
            ->with(
                'tx_mkvarnish_cache_tags',
                [
                    'tag'     => 'test_tag',
                    'cache_hash'    => 'test_hash'
                ]
            );

        $repository = $this->getMock(CacheTagsRepository::class, ['getDatabaseUtility']);
        $repository
            ->expects(self::once())
            ->method('getDatabaseUtility')
            ->will(self::returnValue($databaseUtility));

        $repository->insertByTagAndCacheHash('test_tag', 'test_hash');
    }

    /**
     * @group unit
     */
    public function testGetByCacheHash()
    {
        $databaseUtility = $this->getMock('Tx_Rnbase_Database_Connection', ['doSelect', 'fullQuoteStr']);
        $databaseUtility
            ->expects(self::once())
            ->method('fullQuoteStr')
            ->with('test_hash')
            ->will(self::returnValue('quoted'));

        $databaseUtility
            ->expects(self::once())
            ->method('doSelect')
            ->with(
                '*',
                'tx_mkvarnish_cache_tags',
                [
                    'where' => 'cache_hash = quoted',
                    'enablefieldsoff' => true
                ]
            )
            ->will(self::returnValue(['cacheTags']));;

        $repository = $this->getMock(CacheTagsRepository::class, ['getDatabaseUtility']);
        $repository
            ->expects(self::once())
            ->method('getDatabaseUtility')
            ->will(self::returnValue($databaseUtility));

        self::assertEquals(
            ['cacheTags'],
            $repository->getByCacheHash('test_hash')
        );
    }

    /**
     * @group unit
     */
    public function testDeleteByCacheHash()
    {
        $databaseUtility = $this->getMock('Tx_Rnbase_Database_Connection', ['doDelete', 'fullQuoteStr']);
        $databaseUtility
            ->expects(self::once())
            ->method('fullQuoteStr')
            ->with('test_hash')
            ->will(self::returnValue('quoted'));

        $databaseUtility
            ->expects(self::once())
            ->method('doDelete')
            ->with(
                'tx_mkvarnish_cache_tags',
                'cache_hash = quoted'
            );

        $repository = $this->getMock(CacheTagsRepository::class, ['getDatabaseUtility']);
        $repository
            ->expects(self::once())
            ->method('getDatabaseUtility')
            ->will(self::returnValue($databaseUtility));

        $repository->deleteByCacheHash('test_hash');
    }

    /**
     * @group unit
     */
    public function testTruncateTable()
    {
        $databaseUtility = $this->getMock('Tx_Rnbase_Database_Connection', ['doQuery']);

        $databaseUtility
            ->expects(self::once())
            ->method('doQuery')
            ->with('TRUNCATE tx_mkvarnish_cache_tags');

        $repository = $this->getMock(CacheTagsRepository::class, ['getDatabaseUtility']);
        $repository
            ->expects(self::once())
            ->method('getDatabaseUtility')
            ->will(self::returnValue($databaseUtility));

        $repository->truncateTable();
    }

    /**
     * @group unit
     */
    public function testDeleteCacheTagsByCacheTag()
    {
        $databaseUtility = $this->getMock('Tx_Rnbase_Database_Connection', ['doDelete', 'fullQuoteStr']);
        $databaseUtility
        ->expects(self::once())
        ->method('fullQuoteStr')
        ->with('test_hash')
        ->will(self::returnValue('quoted'));

        $databaseUtility
        ->expects(self::once())
        ->method('doDelete')
        ->with(
            'tx_mkvarnish_cache_tags',
            'cache_hash = quoted'
            );

        $repository = $this->getMock(CacheTagsRepository::class, ['getDatabaseUtility']);
        $repository
        ->expects(self::once())
        ->method('getDatabaseUtility')
        ->will(self::returnValue($databaseUtility));

        $repository->deleteByCacheHash('test_hash');
    }

    /**
     * @group unit
     */
    public function testGetByTag()
    {
        $databaseUtility = $this->getMock('Tx_Rnbase_Database_Connection', ['doSelect', 'fullQuoteStr']);
        $databaseUtility
            ->expects(self::once())
            ->method('fullQuoteStr')
            ->with('test_tag')
            ->will(self::returnValue('quoted'));

        $databaseUtility
            ->expects(self::once())
            ->method('doSelect')
            ->with(
                '*',
                'tx_mkvarnish_cache_tags',
                [
                    'where' => 'tag = quoted',
                    'enablefieldsoff' => true
                ]
            )
            ->will(self::returnValue(['cacheTags']));;

        $repository = $this->getMock(CacheTagsRepository::class, ['getDatabaseUtility']);
        $repository
            ->expects(self::once())
            ->method('getDatabaseUtility')
            ->will(self::returnValue($databaseUtility));

        self::assertEquals(
            ['cacheTags'],
            $repository->getByTag('test_tag')
        );
    }

    /**
     * @group unit
     */
    public function testDeleteByTag()
    {
        $repository = $this->getMock(CacheTagsRepository::class, ['getByTag', 'deleteByCacheHash']);
        $repository
            ->expects(self::at(0))
            ->method('getByTag')
            ->with('test_tag')
            ->will(self::returnValue([0 => ['cache_hash' => 123], 1 => ['cache_hash' => 456]]));
        $repository
            ->expects(self::at(1))
            ->method('deleteByCacheHash')
            ->with(123);
        $repository
            ->expects(self::at(2))
            ->method('deleteByCacheHash')
            ->with(456);

        $repository->deleteByTag('test_tag');
    }
}

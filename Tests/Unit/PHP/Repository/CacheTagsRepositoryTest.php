<?php

namespace DMK\Mkvarnish\Tests\Unit\Repository;

use DMK\Mkvarnish\Repository\CacheTagsRepository;
use Nimut\TestingFramework\TestCase\UnitTestCase;
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
 * DMK\Mkvarnish\Tests\Unit\Hooks$CacheTagsRepositoryTest.
 *
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class CacheTagsRepositoryTest extends UnitTestCase
{
    /**
     * @group unit
     */
    public function testGetDatabaseUtility()
    {
        self::assertInstanceOf(
            Connection::class,
            $this->callInaccessibleMethod(new CacheTagsRepository(), 'getDatabaseUtility')
        );
    }

    /**
     * @group unit
     */
    public function testInsertByTagAndCacheHash()
    {
        $databaseUtility = $this->getMockBuilder(Connection::class)
            ->setMethods(['doInsert'])
            ->getMock();
        $databaseUtility
            ->expects(self::once())
            ->method('doInsert')
            ->with(
                'tx_mkvarnish_cache_tags',
                [
                    'tag' => 'test_tag',
                    'cache_hash' => 'test_hash',
                ]
            );

        $repository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['getDatabaseUtility'])
            ->getMock();
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
        $databaseUtility = $this->getMockBuilder(Connection::class)
            ->setMethods(['doSelect', 'fullQuoteStr'])
            ->getMock();
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
                    'enablefieldsoff' => true,
                ]
            )
            ->will(self::returnValue(['cacheTags']));

        $repository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['getDatabaseUtility'])
            ->getMock();
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
        $databaseUtility = $this->getMockBuilder(Connection::class)
            ->setMethods(['doDelete', 'fullQuoteStr'])
            ->getMock();
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

        $repository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['getDatabaseUtility'])
            ->getMock();
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
        $databaseUtility = $this->getMockBuilder(Connection::class)
            ->setMethods(['doQuery'])
            ->getMock();

        $databaseUtility
            ->expects(self::once())
            ->method('doQuery')
            ->with('TRUNCATE tx_mkvarnish_cache_tags');

        $repository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['getDatabaseUtility'])
            ->getMock();
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
        $databaseUtility = $this->getMockBuilder(Connection::class)
            ->setMethods(['doDelete', 'fullQuoteStr'])
            ->getMock();
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

        $repository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['getDatabaseUtility'])
            ->getMock();
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
        $databaseUtility = $this->getMockBuilder(Connection::class)
            ->setMethods(['doSelect', 'fullQuoteStr'])
            ->getMock();
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
                    'enablefieldsoff' => true,
                ]
            )
            ->will(self::returnValue(['cacheTags']));

        $repository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['getDatabaseUtility'])
            ->getMock();
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
        $repository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['getByTag', 'deleteByCacheHash'])
            ->getMock();
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

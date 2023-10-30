<?php

namespace DMK\Mkvarnish\Tests\Unit\Repository;

use DMK\Mkvarnish\Repository\CacheTagsRepository;
use Doctrine\DBAL\Result;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

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
    public function testGetQueryBuilder()
    {
        $cacheTagsRepository = $this->getAccessibleMock(
            CacheTagsRepository::class,
            ['getQueryBuilder'],
            [],
            '',
            false
        );

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cacheTagsRepository
            ->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        self::assertInstanceOf(
            QueryBuilder::class,
            $cacheTagsRepository->_call('getQueryBuilder')
        );
    }

    /**
     * @group unit
     */
    public function testInsertByTagAndCacheHash()
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['insert', 'values', 'executeQuery'])
            ->getMock();

        $queryBuilder
            ->expects(self::once())
            ->method('insert')
            ->with(
                'tx_mkvarnish_cache_tags'
            )
            ->willReturn($queryBuilder);
        $queryBuilder
            ->expects(self::once())
            ->method('values')
            ->with(
                [
                    'tag' => 'test_tag',
                    'cache_hash' => 'test_hash',
                ]
            )
            ->willReturn($queryBuilder);
        $queryBuilder
            ->expects(self::once())
            ->method('executeQuery');

        $repository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['getQueryBuilder'])
            ->getMock();
        $repository
            ->expects(self::once())
            ->method('getQueryBuilder')
            ->will(self::returnValue($queryBuilder));

        $repository->insertByTagAndCacheHash('test_tag', 'test_hash');
    }

    /**
     * @group unit
     */
    public function testGetByCacheHash()
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['select', 'from', 'where', 'executeQuery', 'expr', 'createNamedParameter'])
            ->getMock();

        $expressionBuilder = $this->getMockBuilder(ExpressionBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['eq'])
            ->getMock();

        $queryBuilder
            ->expects(self::once())
            ->method('select')
            ->with('*')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::once())
            ->method('from')
            ->with('tx_mkvarnish_cache_tags')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::once())
            ->method('expr')
            ->willReturn($expressionBuilder);

        $expressionBuilder
            ->expects(self::once())
            ->method('eq')
            ->with('cache_hash', 'test_hash')
            ->willReturn('expression');

        $queryBuilder
            ->expects(self::once())
            ->method('where')
            ->with('expression')
            ->willReturn($queryBuilder);

        $result = $this->getMockBuilder(Result::class)->disableOriginalConstructor()->getMock();
        $resultArray = new \ArrayObject(['test']);
        $result
            ->expects(self::once())
            ->method('iterateAssociative')
            ->willReturn($resultArray);
        $queryBuilder
            ->expects(self::once())
            ->method('executeQuery')
            ->willReturn($result);

        $queryBuilder
            ->expects(self::once())
            ->method('createNamedParameter')
            ->with('test_hash')
            ->willReturn('test_hash');

        $repository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['getQueryBuilder'])
            ->getMock();
        $repository
            ->expects(self::once())
            ->method('getQueryBuilder')
            ->will(self::returnValue($queryBuilder));

        self::assertSame($resultArray, $repository->getByCacheHash('test_hash'));
    }

    /**
     * @group unit
     */
    public function testDeleteByCacheHash()
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['delete', 'where', 'executeQuery', 'expr', 'createNamedParameter'])
            ->getMock();

        $expressionBuilder = $this->getMockBuilder(ExpressionBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['eq'])
            ->getMock();

        $queryBuilder
            ->expects(self::once())
            ->method('delete')
            ->with('tx_mkvarnish_cache_tags')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::once())
            ->method('expr')
            ->willReturn($expressionBuilder);

        $expressionBuilder
            ->expects(self::once())
            ->method('eq')
            ->with('cache_hash', 'test_hash')
            ->willReturn('expression');

        $queryBuilder
            ->expects(self::once())
            ->method('where')
            ->with('expression')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::once())
            ->method('executeQuery');

        $queryBuilder
            ->expects(self::once())
            ->method('createNamedParameter')
            ->with('test_hash')
            ->willReturn('test_hash');

        $repository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['getQueryBuilder'])
            ->getMock();
        $repository
            ->expects(self::once())
            ->method('getQueryBuilder')
            ->will(self::returnValue($queryBuilder));

        $repository->deleteByCacheHash('test_hash');
    }

    /**
     * @group unit
     */
    public function testTruncateTable()
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection', 'createNamedParameter'])
            ->getMock();

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['truncate'])
            ->getMock();

        $queryBuilder
            ->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $connection
            ->expects(self::once())
            ->method('truncate')
            ->with('tx_mkvarnish_cache_tags')
            ->willReturn(1);

        $repository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['getQueryBuilder'])
            ->getMock();
        $repository
            ->expects(self::once())
            ->method('getQueryBuilder')
            ->will(self::returnValue($queryBuilder));

        $repository->truncateTable();
    }

    /**
     * @group unit
     */
    public function testGetByTag()
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['select', 'from', 'where', 'executeQuery', 'expr', 'createNamedParameter'])
            ->getMock();

        $expressionBuilder = $this->getMockBuilder(ExpressionBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['eq'])
            ->getMock();

        $queryBuilder
            ->expects(self::once())
            ->method('select')
            ->with('*')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::once())
            ->method('from')
            ->with('tx_mkvarnish_cache_tags')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::once())
            ->method('expr')
            ->willReturn($expressionBuilder);

        $expressionBuilder
            ->expects(self::once())
            ->method('eq')
            ->with('tag', 'test_tag')
            ->willReturn('expression');

        $queryBuilder
            ->expects(self::once())
            ->method('where')
            ->with('expression')
            ->willReturn($queryBuilder);

        $result = $this->getMockBuilder(Result::class)->disableOriginalConstructor()->getMock();
        $resultArray = new \ArrayObject(['test']);
        $result
            ->expects(self::once())
            ->method('iterateAssociative')
            ->willReturn($resultArray);
        $queryBuilder
            ->expects(self::once())
            ->method('executeQuery')
            ->willReturn($result);

        $queryBuilder
            ->expects(self::once())
            ->method('createNamedParameter')
            ->with('test_tag')
            ->willReturn('test_tag');

        $repository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['getQueryBuilder'])
            ->getMock();
        $repository
            ->expects(self::once())
            ->method('getQueryBuilder')
            ->will(self::returnValue($queryBuilder));

        self::assertSame($resultArray, $repository->getByTag('test_tag'));
    }

    /**
     * @group unit
     */
    public function testDeleteByTag()
    {
        $repository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['getByTag', 'deleteByCacheHash', 'createNamedParameter'])
            ->getMock();
        $repository
            ->expects(self::once())
            ->method('getByTag')
            ->with('test_tag')
            ->willReturn(new \ArrayObject([
                0 => ['cache_hash' => 123],
                1 => ['cache_hash' => 456],
            ]));

        $repository
            ->expects(self::exactly(2))
            ->method('deleteByCacheHash')
            ->withConsecutive([123], [456]);

        $repository->deleteByTag('test_tag');
    }
}

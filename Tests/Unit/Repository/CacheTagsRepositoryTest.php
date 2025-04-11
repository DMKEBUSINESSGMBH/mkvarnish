<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mklog" Extension for TYPO3 CMS.
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
    public function testGetQueryBuilder(): void
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

    public function testInsertByTagAndCacheHash(): void
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['insert', 'values', 'executeStatement'])
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
            ->method('executeStatement');

        $repository = $this->getMockBuilder(CacheTagsRepository::class)
            ->onlyMethods(['getQueryBuilder'])
            ->getMock();
        $repository
            ->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $repository->insertByTagAndCacheHash('test_tag', 'test_hash');
    }

    public function testGetByCacheHash(): void
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['select', 'from', 'where', 'executeQuery', 'expr', 'createNamedParameter'])
            ->getMock();

        $expressionBuilder = $this->getMockBuilder(ExpressionBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['eq'])
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
            ->onlyMethods(['getQueryBuilder'])
            ->getMock();
        $repository
            ->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        self::assertSame($resultArray, $repository->getByCacheHash('test_hash'));
    }

    public function testDeleteByCacheHash(): void
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['delete', 'where', 'executeStatement', 'expr', 'createNamedParameter'])
            ->getMock();

        $expressionBuilder = $this->getMockBuilder(ExpressionBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['eq'])
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
            ->method('executeStatement');

        $queryBuilder
            ->expects(self::once())
            ->method('createNamedParameter')
            ->with('test_hash')
            ->willReturn('test_hash');

        $repository = $this->getMockBuilder(CacheTagsRepository::class)
            ->onlyMethods(['getQueryBuilder'])
            ->getMock();
        $repository
            ->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $repository->deleteByCacheHash('test_hash');
    }

    public function testTruncateTable(): void
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConnection', 'createNamedParameter'])
            ->getMock();

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['truncate'])
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
            ->onlyMethods(['getQueryBuilder'])
            ->getMock();
        $repository
            ->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $repository->truncateTable();
    }

    public function testGetByTag(): void
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['select', 'from', 'where', 'executeQuery', 'expr', 'createNamedParameter'])
            ->getMock();

        $expressionBuilder = $this->getMockBuilder(ExpressionBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['eq'])
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
            ->onlyMethods(['getQueryBuilder'])
            ->getMock();
        $repository
            ->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        self::assertSame($resultArray, $repository->getByTag('test_tag'));
    }

    public function testDeleteByTag(): void
    {
        $repository = $this->getMockBuilder(CacheTagsRepository::class)
            ->onlyMethods(['getByTag', 'deleteByCacheHash'])
            ->getMock();
        $repository
            ->expects(self::once())
            ->method('getByTag')
            ->with('test_tag')
            ->willReturn(new \ArrayObject([
                0 => ['cache_hash' => 123],
                1 => ['cache_hash' => 456],
            ]));

        $matcher = self::exactly(2);
        $repository
            ->expects($matcher)
            ->method('deleteByCacheHash')
            ->with(
                $this->callback(function (string $cacheHash) use ($matcher): bool {
                    self::assertSame(
                        match ($matcher->numberOfInvocations()) {
                            1 => '123',
                            2 => '456',
                        },
                        $cacheHash
                    );

                    return true;
                }),
            );

        $repository->deleteByTag('test_tag');
    }
}

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

namespace DMK\Mkvarnish\Tests\Unit\Cache;

use DMK\Mkvarnish\Cache\VarnishBackend;
use DMK\Mkvarnish\Repository\CacheTagsRepository;
use DMK\Mkvarnish\Utility\Configuration;
use DMK\Mkvarnish\Utility\CurlQueue;
use PHPUnit\Framework\Attributes\DataProvider;
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
 * DMK\Mkvarnish\Tests\Unit\Hooks$VarnishBackendTest.
 *
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class VarnishBackendTest extends UnitTestCase
{
    /**
     * @var string
     */
    private mixed $siteNameBackup;

    /**
     * @var array
     */
    private mixed $extConfBackup = [];

    /**
     * @var string|null
     */
    private mixed $encryptionKeyBackup;

    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        $this->siteNameBackup = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        $this->extConfBackup = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['mkvarnish'] ?? [];
        $this->encryptionKeyBackup = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] ?? [];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'test';
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = $this->siteNameBackup;
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['mkvarnish'] = $this->extConfBackup;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = $this->encryptionKeyBackup;
        parent::tearDown();
    }

    public function testThrowExceptionIfNotImplemented(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('the varnish cache backend can only remove cache entries by tags or the complete cache at the moment');
        $this->getAccessibleMock(VarnishBackend::class, ['has'], ['testing'])->_call('throwExceptionIfNotImplemented');
    }

    /**
     * @dataProvider dataProviderUnimplementedMethods
     */
    #[DataProvider('dataProviderUnimplementedMethods')]
    public function testUnimplementedMethods(string $method, array $arguments): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('the varnish cache backend can only remove cache entries by tags or the complete cache at the moment');
        $varnishBackend = new VarnishBackend('testing');

        call_user_func_array([$varnishBackend, $method], $arguments);
    }

    /**
     * @return string[][]|string[][][]
     */
    public static function dataProviderUnimplementedMethods(): array
    {
        return [
            'method set, line: '.__LINE__ => ['set', ['test', []]],
            'method get, line: '.__LINE__ => ['get', ['test']],
            'method remove, line: '.__LINE__ => ['remove', ['test']],
            'method has, line: '.__LINE__ => ['has', ['test']],
            'method findIdentifiersByTag, line: '.__LINE__ => ['findIdentifiersByTag', ['test']],
        ];
    }

    public function testGetHmacForSitename(): void
    {
        $varnishBackend = $this->getVarnishBackendInstance();
        $firstHmac = $varnishBackend->_call('getHmacForSitename');
        $secondHmac = $varnishBackend->_call('getHmacForSitename');

        self::assertSame($firstHmac, $secondHmac, 'hmac for sitename is not same in 2 calls');
        self::assertIsString($firstHmac, 'hmac is no string');
        self::assertGreaterThan(30, strlen($firstHmac), 'hmac is not at least 30 chars long');

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = 'test site mkvarnish';
        $hmacAfterSiteNameChanged = $varnishBackend->_call('getHmacForSitename');
        self::assertNotSame($firstHmac, $hmacAfterSiteNameChanged, 'hmac for different site names is not different');
    }

    public function testConvertCacheTagForPurgeWithRecordTag(): void
    {
        $convertedCacheTagForPurge = $this->getVarnishBackendInstance()->_call(
            'convertCacheTagForPurge',
            'tt_content_5'
        );

        self::assertEquals('(^|,)tt_content\{[^}]*,5,', $convertedCacheTagForPurge);

        // the regex must match the compressed representation of the tag ...
        self::assertSame(1, preg_match('/'.$convertedCacheTagForPurge.'/', 'pages,tt_content{,3,5,8,}'));
        self::assertSame(1, preg_match('/'.$convertedCacheTagForPurge.'/', 'tt_content{,5,}'));
        // ... but not a different uid which only shares a common prefix
        self::assertSame(0, preg_match('/'.$convertedCacheTagForPurge.'/', 'tt_content{,15,58,}'));
        // ... and not a different table which only shares a common suffix
        self::assertSame(0, preg_match('/'.$convertedCacheTagForPurge.'/', 'tx_foo_tt_content{,5,}'));
    }

    public function testConvertCacheTagForPurgeWithTableOrCustomTag(): void
    {
        $convertedCacheTagForPurge = $this->getVarnishBackendInstance()->_call(
            'convertCacheTagForPurge',
            'tt_content'
        );

        self::assertEquals('(^|,)tt_content(\{|,|$)', $convertedCacheTagForPurge);

        // matches a standalone token ...
        self::assertSame(1, preg_match('/'.$convertedCacheTagForPurge.'/', 'pages,tt_content'));
        self::assertSame(1, preg_match('/'.$convertedCacheTagForPurge.'/', 'tt_content,pages'));
        // ... as well as the prefix of a compressed group ...
        self::assertSame(1, preg_match('/'.$convertedCacheTagForPurge.'/', 'pages,tt_content{,3,5,}'));
        // ... but not a table which only shares a common prefix
        self::assertSame(0, preg_match('/'.$convertedCacheTagForPurge.'/', 'tt_content_foo{,5,}'));
    }

    public function testGetHostNamesForPurge(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['mkvarnish']['hostnames'] = '127.0.0.1';
        $varnishBackend = $this->getVarnishBackendInstance();

        self::assertContains(
            '127.0.0.1',
            $varnishBackend->_call('getHostNamesForPurge')
        );
    }

    public function testGetCurlQueueUtility(): void
    {
        self::assertInstanceOf(
            CurlQueue::class,
            $this->getVarnishBackendInstance()->_call('getCurlQueueUtility')
        );
    }

    public function testExecutePurge(): void
    {
        $varnishBackend = $this->getAccessibleMock(
            VarnishBackend::class,
            ['getHmacForSitename', 'getCurlQueueUtility', 'getHostNamesForPurge'],
            ['testing']
        );

        $varnishBackend
            ->expects(self::once())
            ->method('getHmacForSitename')
            ->willReturn('abc123');

        $varnishBackend
            ->expects(self::once())
            ->method('getHostNamesForPurge')
            ->willReturn(['firstHost', 'secondHost']);

        $curlQueueUtility = $this->getMockBuilder(CurlQueue::class)
            ->onlyMethods(['addCommand'])
            ->getMock();

        $matcher = self::exactly(2);
        $curlQueueUtility
            ->expects($matcher)
            ->method('addCommand')
            ->with(
                'PURGE',
                $this->callback(function (string $host) use ($matcher): bool {
                    self::assertSame(
                        match ($matcher->numberOfInvocations()) {
                            1 => 'firstHost',
                            2 => 'secondHost',
                        },
                        $host
                    );

                    return true;
                }),
                $this->callback(function (array $headers) use ($matcher): bool {
                    self::assertSame(
                        match ($matcher->numberOfInvocations()) {
                            1 => ['X-Varnish-Purge-All: 1', 'X-TYPO3-Sitename: abc123'],
                            2 => ['X-Varnish-Purge-All: 1', 'X-TYPO3-Sitename: abc123'],
                        },
                        $headers
                    );

                    return true;
                }),
            )
            ->willReturnOnConsecutiveCalls(
                $curlQueueUtility,
                $curlQueueUtility
            );

        $varnishBackend
            ->expects(self::once())
            ->method('getCurlQueueUtility')
            ->willReturn($curlQueueUtility);

        $varnishBackend->_call('executePurge', ['X-Varnish-Purge-All' => 1]);
    }

    public function testFlush(): void
    {
        $configurationUtility = $this->getMockBuilder(Configuration::class)
            ->onlyMethods(['isSendCacheHeadersEnabled'])
            ->getMock();
        $configurationUtility
            ->expects(self::once())
            ->method('isSendCacheHeadersEnabled')
            ->willReturn(true);

        $varnishBackend = $this->getMockBuilder(VarnishBackend::class)
            ->onlyMethods(['executePurge', 'truncateCacheTagsTable', 'getConfigurationUtility'])
            ->disableOriginalConstructor()
            ->getMock();

        $varnishBackend
            ->expects(self::once())
            ->method('getConfigurationUtility')
            ->willReturn($configurationUtility);

        $varnishBackend
            ->expects(self::once())
            ->method('executePurge')
            ->with(['X-Varnish-Purge-All' => 1]);

        $varnishBackend
            ->expects(self::once())
            ->method('truncateCacheTagsTable');

        $varnishBackend->flush();
    }

    public function testFlushWhenNotSendCacheHeaderEnabled(): void
    {
        $configurationUtility = $this->getMockBuilder(Configuration::class)
            ->onlyMethods(['isSendCacheHeadersEnabled'])
            ->getMock();
        $configurationUtility
            ->expects(self::once())
            ->method('isSendCacheHeadersEnabled')
            ->willReturn(false);

        $varnishBackend = $this->getMockBuilder(VarnishBackend::class)
            ->onlyMethods(['executePurge', 'truncateCacheTagsTable', 'getConfigurationUtility'])
            ->disableOriginalConstructor()
            ->getMock();

        $varnishBackend
            ->expects(self::once())
            ->method('getConfigurationUtility')
            ->willReturn($configurationUtility);

        $varnishBackend
            ->expects(self::never())
            ->method('executePurge');

        $varnishBackend
            ->expects(self::never())
            ->method('truncateCacheTagsTable');

        $varnishBackend->flush();
    }

    public function testFlushByTag(): void
    {
        $configurationUtility = $this->getMockBuilder(Configuration::class)
            ->onlyMethods(['isSendCacheHeadersEnabled'])
            ->getMock();
        $configurationUtility
            ->expects(self::once())
            ->method('isSendCacheHeadersEnabled')
            ->willReturn(true);

        $varnishBackend = $this->getMockBuilder(VarnishBackend::class)
            ->onlyMethods(['executePurge', 'convertCacheTagForPurge', 'deleteFromCacheTagsTableByTag', 'getConfigurationUtility'])
            ->disableOriginalConstructor()
            ->getMock();

        $varnishBackend
            ->expects(self::once())
            ->method('getConfigurationUtility')
            ->willReturn($configurationUtility);

        $varnishBackend
            ->expects(self::once())
            ->method('convertCacheTagForPurge')
            ->with('testTag')
            ->willReturn('convertedTag');

        $varnishBackend
            ->expects(self::once())
            ->method('executePurge')
            ->with(['X-Cache-Tags' => 'convertedTag']);

        $varnishBackend
            ->expects(self::once())
            ->method('deleteFromCacheTagsTableByTag')
            ->with('testTag');

        $varnishBackend->flushByTag('testTag');
    }

    public function testFlushByTagWhenNotSendCacheHeaderEnabled(): void
    {
        $configurationUtility = $this->getMockBuilder(Configuration::class)
            ->onlyMethods(['isSendCacheHeadersEnabled'])
            ->getMock();
        $configurationUtility
            ->expects(self::once())
            ->method('isSendCacheHeadersEnabled')
            ->willReturn(false);

        $varnishBackend = $this->getMockBuilder(VarnishBackend::class)
            ->onlyMethods(['executePurge', 'convertCacheTagForPurge', 'deleteFromCacheTagsTableByTag', 'getConfigurationUtility'])
            ->disableOriginalConstructor()
            ->getMock();

        $varnishBackend
            ->expects(self::once())
            ->method('getConfigurationUtility')
            ->willReturn($configurationUtility);

        $varnishBackend
            ->expects(self::never())
            ->method('convertCacheTagForPurge');

        $varnishBackend
            ->expects(self::never())
            ->method('executePurge');

        $varnishBackend
            ->expects(self::never())
            ->method('deleteFromCacheTagsTableByTag');

        $varnishBackend->flushByTag('testTag');
    }

    public function testGetCacheTagsRepository(): void
    {
        self::assertInstanceOf(
            CacheTagsRepository::class,
            $this->getVarnishBackendInstance()->_call('getCacheTagsRepository')
        );
    }

    public function testTruncateCacheTagsTable(): void
    {
        $cacheTagsRepository = $this->getMockBuilder(CacheTagsRepository::class)
            ->onlyMethods(['truncateTable'])
            ->getMock();

        $cacheTagsRepository
            ->expects(self::once())
            ->method('truncateTable');

        $varnishBackend = $this->getAccessibleMock(
            VarnishBackend::class,
            ['getCacheTagsRepository'],
            ['testing']
        );
        $varnishBackend
            ->expects(self::once())
            ->method('getCacheTagsRepository')
            ->willReturn($cacheTagsRepository);

        $varnishBackend->_call('truncateCacheTagsTable');
    }

    public function testDeleteFromCacheTagsTableByTag(): void
    {
        $cacheTagsRepository = $this->getMockBuilder(CacheTagsRepository::class)
            ->onlyMethods(['deleteByTag'])
            ->getMock();

        $cacheTagsRepository
            ->expects(self::once())
            ->method('deleteByTag')
            ->with('test_tag');

        $varnishBackend = $this->getAccessibleMock(
            VarnishBackend::class,
            ['getCacheTagsRepository'],
            ['testing']
        );
        $varnishBackend
            ->expects(self::once())
            ->method('getCacheTagsRepository')
            ->willReturn($cacheTagsRepository);

        $varnishBackend->_call('deleteFromCacheTagsTableByTag', 'test_tag');
    }

    public function testGetConfigurationUtility(): void
    {
        self::assertInstanceOf(
            Configuration::class,
            $this->getVarnishBackendInstance()->_call('getConfigurationUtility')
        );
    }

    /**
     * @return VarnishBackend
     */
    private function getVarnishBackendInstance()
    {
        return $this->getAccessibleMock(VarnishBackend::class, ['has'], ['testing']);
    }
}

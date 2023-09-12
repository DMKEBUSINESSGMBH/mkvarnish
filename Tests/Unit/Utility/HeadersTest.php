<?php

namespace DMK\Mkvarnish\Tests\Unit\Utility;

use DMK\Mkvarnish\Repository\CacheTagsRepository;
use DMK\Mkvarnish\Utility\Configuration;
use DMK\Mkvarnish\Utility\Headers;
use Doctrine\DBAL\Cache\ArrayStatement;
use Doctrine\DBAL\ForwardCompatibility\Result;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

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
 * HeadersTest.
 *
 * @author Hannes Bochmann
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class HeadersTest extends UnitTestCase
{
    /**
     * {@inheritdoc}
     *
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        if (isset($GLOBALS['TSFE'])) {
            unset($GLOBALS['TSFE']);
        }
        parent::tearDown();
    }

    /**
     * Test the get method.
     *
     * @return void
     *
     * @group unit
     *
     * @test
     */
    public function testGetWithoutVarnish()
    {
        $configuration = $this->getMockBuilder(Configuration::class)
            ->setMethods(['isSendCacheHeadersEnabled'])
            ->getMock();
        $configuration->expects($this->once())->method('isSendCacheHeadersEnabled')->will($this->returnValue(false));
        $cacheTagsRepository = $this->getMockBuilder(CacheTagsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $headersUtility = new Headers($cacheTagsRepository, $configuration);

        $headers = $headersUtility->get();

        $this->assertTrue(is_array($headers));
        $this->assertEmpty($headers);
    }

    /**
     * Test the get method.
     *
     * @return void
     *
     * @group unit
     *
     * @test
     */
    public function testGetBehindVarnish()
    {
        $configuration = $this->getMockBuilder(Configuration::class)
            ->setMethods(['isSendCacheHeadersEnabled'])
            ->getMock();
        $configuration->expects($this->once())->method('isSendCacheHeadersEnabled')->will($this->returnValue(true));
        $cacheTagsRepository = $this->getMockBuilder(CacheTagsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        // prepare tsfe
        $tsfe = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tsfe->newHash = 'asd123hjk678';
        $tsfe->config['INTincScript'] = ['one', 'two'];

        $headersUtility = $this->getMockBuilder(Headers::class)
            ->setMethods(['getTsFe', 'getHeadersForCacheTags', 'getHmacForSitename'])
            ->setConstructorArgs([$cacheTagsRepository, $configuration])
            ->getMock();

        $headersUtility->expects($this->any())->method('getTsFe')->will($this->returnValue($tsfe));
        $headersUtility->expects($this->once())->method('getHmacForSitename')->will($this->returnValue('345dfg'));
        $headersUtility
            ->expects($this->once())
            ->method('getHeadersForCacheTags')
            ->will($this->returnValue(['X-Cache-Tags' => 'pages,pages_419']))
        ;

        $headers = $headersUtility->get();

        $this->assertTrue(is_array($headers));
        $this->assertCount(4, $headers);

        $this->assertArrayHasKey('X-Cache-Tags', $headers);
        $this->assertEquals('pages,pages_419', $headers['X-Cache-Tags']);
        $this->assertArrayHasKey('X-TYPO3-Sitename', $headers);
        $this->assertEquals('345dfg', $headers['X-TYPO3-Sitename']);
        $this->assertArrayHasKey('X-TYPO3-cHash', $headers);
        $this->assertEquals('asd123hjk678', $headers['X-TYPO3-cHash']);
        $this->assertArrayHasKey('X-TYPO3-INTincScripts', $headers);
        $this->assertEquals(2, $headers['X-TYPO3-INTincScripts']);
    }

    /**
     * Test the get method.
     *
     * @return void
     *
     * @group unit
     *
     * @test
     */
    public function testGetBehindVarnishButNotLive()
    {
        if (!is_object($GLOBALS['BE_USER'] ?? null)) {
            $GLOBALS['BE_USER'] = new \stdClass();
            $GLOBALS['BE_USER']->workspace = 123;
        }

        $configuration = $this->getMockBuilder(Configuration::class)
            ->setMethods(['isSendCacheHeadersEnabled'])
            ->getMock();
        $configuration->expects($this->once())->method('isSendCacheHeadersEnabled')->will($this->returnValue(true));
        $cacheTagsRepository = $this->getMockBuilder(CacheTagsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        // prepare tsfe
        $tsfe = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tsfe->newHash = 'asd123hjk678';
        $tsfe->config['INTincScript'] = ['one', 'two'];

        $headersUtility = new Headers($cacheTagsRepository, $configuration);
        $headers = $headersUtility->get();

        $this->assertTrue(is_array($headers));
        $this->assertEmpty($headers);
    }

    /**
     * @return void
     *
     * @test
     */
    public function testGetHeadersForCacheTagsIfCacheTagsPresent()
    {
        $tsfe = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->setMethods(['determineId'])
            ->disableOriginalConstructor()
            ->getMock();
        $tsfe->newHash = 123;
        $tsfe->addCacheTags(['tag1', 'tag2', 'tag2']);

        $headersUtility = $this->getMockBuilder(Headers::class)
            ->setMethods(['getTsFe', 'saveCacheTagsByCacheHash'])
            ->disableOriginalConstructor()
            ->getMock();
        $headersUtility->expects($this->any())->method('getTsFe')->will($this->returnValue($tsfe));
        $headersUtility->expects($this->once())->method('saveCacheTagsByCacheHash')->with(['tag1', 'tag2'], 123);
        $headers = $this->callInaccessibleMethod($headersUtility, 'getHeadersForCacheTags');

        $this->assertTrue(is_array($headers));
        $this->assertCount(1, $headers);

        $this->assertArrayHasKey('X-Cache-Tags', $headers);
        $this->assertEquals('tag1,tag2', $headers['X-Cache-Tags']);
    }

    /**
     * @return void
     *
     * @test
     */
    public function testGetHeadersForCacheTagsIfCacheTagsNotPresent()
    {
        $tsfe = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->setMethods(['determineId'])
            ->disableOriginalConstructor()
            ->getMock();
        $tsfe->newHash = 123;
        $headersUtility = $this->getMockBuilder(Headers::class)
            ->setMethods(['getTsFe', 'getCacheTagsByCacheHash'])
            ->disableOriginalConstructor()
            ->getMock();
        $headersUtility->expects($this->any())->method('getTsFe')->will($this->returnValue($tsfe));
        $headersUtility
            ->expects(self::once())
            ->method('getCacheTagsByCacheHash')
            ->with(123)
            ->will(self::returnValue(['tag1', 'tag2']));

        $headers = $this->callInaccessibleMethod($headersUtility, 'getHeadersForCacheTags');

        $this->assertTrue(is_array($headers));
        $this->assertCount(1, $headers);

        $this->assertArrayHasKey('X-Cache-Tags', $headers);
        $this->assertEquals('tag1,tag2', $headers['X-Cache-Tags']);
    }

    /**
     * @return void
     *
     * @test
     */
    public function testSaveCacheTagsByCacheHash()
    {
        $configuration = $this->getMockBuilder(Configuration::class)->getMock();
        $cacheTagsRepository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['insertByTagAndCacheHash', 'deleteByCacheHash'])
            ->getMock();

        $cacheTagsRepository
            ->expects(self::once())
            ->method('deleteByCacheHash')
            ->with(123);

        $cacheTagsRepository
            ->expects(self::exactly(2))
            ->method('insertByTagAndCacheHash')
            ->withConsecutive(['tag_1', 123], ['tag_2', 123]);

        $headersUtility = new Headers($cacheTagsRepository, $configuration);
        $this->callInaccessibleMethod($headersUtility, 'saveCacheTagsByCacheHash', ['tag_1', 'tag_2'], 123);
    }

    /**
     * @return void
     *
     * @test
     */
    public function testGetCacheTagsByCacheHash()
    {
        $configuration = $this->getMockBuilder(Configuration::class)->getMock();
        $cacheTagsRepository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['getByCacheHash'])
            ->getMock();

        $cacheTagsRepository
            ->expects(self::once())
            ->method('getByCacheHash')
            ->with(123)
            ->will(self::returnValue(new Result(new ArrayStatement([
                0 => ['cache_hash' => 123, 'tag' => 'tag_1'],
                1 => ['cache_hash' => 123, 'tag' => 'tag_2'],
            ]))));

        $headersUtility = new Headers($cacheTagsRepository, $configuration);

        self::assertEquals(
            ['tag_1', 'tag_2'],
            $this->callInaccessibleMethod($headersUtility, 'getCacheTagsByCacheHash', 123)
        );
    }

    /**
     * @return void
     *
     * @test
     */
    public function testGetCurrentCacheHash()
    {
        $pageArguments = $this->getMockBuilder(PageArguments::class)
            ->setMethods(['dummy'])
            ->setConstructorArgs([123, '', ['cHash' => 123]])
            ->getMock();
        $tsfe = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->setMethods(['determineId', 'getPageArguments'])
            ->disableOriginalConstructor()
            ->getMock();
        $tsfe
            ->expects($this->any())
            ->method('getPageArguments')
            ->will($this->returnValue($pageArguments));
        $headersUtility = $this->getMockBuilder(Headers::class)
            ->setMethods(['getTsFe', 'getCacheTagsByCacheHash'])
            ->disableOriginalConstructor()
            ->getMock();
        $headersUtility
            ->expects($this->any())
            ->method('getTsFe')
            ->will($this->returnValue($tsfe));

        self::assertEquals(123, $this->callInaccessibleMethod($headersUtility, 'getCurrentCacheHash'));

        $tsfe->newHash = 456;
        self::assertEquals(456, $this->callInaccessibleMethod($headersUtility, 'getCurrentCacheHash'));
    }
}

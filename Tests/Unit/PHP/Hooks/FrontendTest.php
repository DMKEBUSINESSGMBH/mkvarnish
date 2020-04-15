<?php

namespace DMK\Mkvarnish\Tests\Unit\Hooks;

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

use DMK\Mkvarnish\Hook\Frontend;
use DMK\Mkvarnish\Repository\CacheTagsRepository;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * This class communicates with the varnish server.
 *
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class FrontendTest extends UnitTestCase
{
    /**
     * {@inheritdoc}
     *
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        if (isset($GLOBALS['TSFE'])) {
            unset($GLOBALS['TSFE']);
        }
        parent::tearDown();
    }

    /**
     * Test the handleHeaders method.
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testHandleHeadersWithHeaders()
    {
        $headers['name'] = 'wert';

        $mock = $this->getMockBuilder(Frontend::class)
            ->setMethods(['isSendCacheHeadersEnabled', 'getTsFe', 'getHeaders', 'sendHeaders'])
            ->getMock();

        $mock->expects($this->once())->method('getHeaders')->will($this->returnValue($headers));
        $mock->expects($this->once())->method('sendHeaders')->with($headers);

        $mock->handleHeaders();
    }

    /**
     * Test the handleHeaders method.
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testHandleHeadersWithoutHeaders()
    {
        $mock = $this->getMockBuilder(Frontend::class)
            ->setMethods(['isSendCacheHeadersEnabled', 'getTsFe', 'getHeaders', 'sendHeaders'])
            ->getMock();

        $mock->expects($this->once())->method('getHeaders')->will($this->returnValue([]));
        $mock->expects($this->never())->method('sendHeaders');

        $mock->handleHeaders();
    }

    /**
     * Test the getHeaders method.
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testGetHeadersWithoutVarnish()
    {
        $mock = $this->getMockBuilder(Frontend::class)
            ->setMethods(['isSendCacheHeadersEnabled'])
            ->getMock();

        $mock->expects($this->once())->method('isSendCacheHeadersEnabled')->will($this->returnValue(false));

        $headers = $this->callInaccessibleMethod(
            $mock,
            'getHeaders'
        );

        $this->assertTrue(is_array($headers));
        $this->assertEmpty($headers);
    }

    /**
     * Test the getHeaders method.
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testGetHeadersBehindVarnish()
    {
        // prepare tsfe
        $tsfe = new \stdclass();
        $tsfe->newHash = 'asd123hjk678';
        $tsfe->config['INTincScript'] = ['one', 'two'];

        $mock = $this->getMockBuilder(Frontend::class)
            ->setMethods(['isSendCacheHeadersEnabled', 'getTsFe', 'getHeadersForCacheTags', 'getHmacForSitename'])
            ->getMock();

        $mock->expects($this->once())->method('isSendCacheHeadersEnabled')->will($this->returnValue(true));
        $mock->expects($this->any())->method('getTsFe')->will($this->returnValue($tsfe));
        $mock->expects($this->once())->method('getHmacForSitename')->will($this->returnValue('345dfg'));
        ($mock
            ->expects($this->once())
            ->method('getHeadersForCacheTags')
            ->will($this->returnValue(['X-Cache-Tags' => 'pages,pages_419']))
        );

        $headers = $this->callInaccessibleMethod(
            $mock,
            'getHeaders'
        );

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
     * @return void
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

        $hook = $this->getMockBuilder(Frontend::class)
            ->setMethods(['getTsFe', 'saveCacheTagsByCacheHash'])
            ->getMock();
        $hook->expects($this->any())->method('getTsFe')->will($this->returnValue($tsfe));
        $hook->expects($this->once())->method('saveCacheTagsByCacheHash')->with(['tag1', 'tag2'], 123);
        $headers = $this->callInaccessibleMethod($hook, 'getHeadersForCacheTags');

        $this->assertTrue(is_array($headers));
        $this->assertCount(1, $headers);

        $this->assertArrayHasKey('X-Cache-Tags', $headers);
        $this->assertEquals('tag1,tag2', $headers['X-Cache-Tags']);
    }

    /**
     * @return void
     * @test
     */
    public function testGetHeadersForCacheTagsIfCacheTagsNotPresent()
    {
        $tsfe = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->setMethods(['determineId'])
            ->disableOriginalConstructor()
            ->getMock();
        $tsfe->newHash = 123;
        $hook = $this->getMockBuilder(Frontend::class)
            ->setMethods(['getTsFe', 'getCacheTagsByCacheHash'])
            ->getMock();
        $hook->expects($this->any())->method('getTsFe')->will($this->returnValue($tsfe));
        $hook
            ->expects(self::once())
            ->method('getCacheTagsByCacheHash')
            ->with(123)
            ->will(self::returnValue(['tag1', 'tag2']));

        $headers = $this->callInaccessibleMethod($hook, 'getHeadersForCacheTags');

        $this->assertTrue(is_array($headers));
        $this->assertCount(1, $headers);

        $this->assertArrayHasKey('X-Cache-Tags', $headers);
        $this->assertEquals('tag1,tag2', $headers['X-Cache-Tags']);
    }

    /**
     * @group unit
     */
    public function testGetCacheTagsRepository()
    {
        self::assertInstanceOf(
            CacheTagsRepository::class,
            $this->callInaccessibleMethod(new Frontend(), 'getCacheTagsRepository')
        );
    }

    /**
     * @return void
     * @test
     */
    public function testSaveCacheTagsByCacheHash()
    {
        $cacheTagsRepository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['insertByTagAndCacheHash', 'deleteByCacheHash'])
            ->getMock();

        $cacheTagsRepository
            ->expects(self::at(0))
            ->method('deleteByCacheHash')
            ->with(123);
        $cacheTagsRepository
            ->expects(self::at(1))
            ->method('insertByTagAndCacheHash')
            ->with('tag_1', 123);
        $cacheTagsRepository
            ->expects(self::at(2))
            ->method('insertByTagAndCacheHash')
            ->with('tag_2', 123);

        $hook = $this->getMockBuilder(Frontend::class)
            ->setMethods(['getCacheTagsRepository'])
            ->getMock();
        $hook
            ->expects(self::once())
            ->method('getCacheTagsRepository')
            ->will($this->returnValue($cacheTagsRepository));

        $this->callInaccessibleMethod($hook, 'saveCacheTagsByCacheHash', ['tag_1', 'tag_2'], 123);
    }

    /**
     * @return void
     * @test
     */
    public function testGetCacheTagsByCacheHash()
    {
        $cacheTagsRepository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['getByCacheHash'])
            ->getMock();

        $cacheTagsRepository
            ->expects(self::once())
            ->method('getByCacheHash')
            ->with(123)
            ->will(self::returnValue([
                0 => ['cache_hash' => 123, 'tag' => 'tag_1'],
                1 => ['cache_hash' => 123, 'tag' => 'tag_2'],
            ]));

        $hook = $this->getMockBuilder(Frontend::class)
            ->setMethods(['getCacheTagsRepository'])
            ->getMock();
        $hook
            ->expects(self::once())
            ->method('getCacheTagsRepository')
            ->will($this->returnValue($cacheTagsRepository));

        self::assertEquals(
            ['tag_1', 'tag_2'],
            $this->callInaccessibleMethod($hook, 'getCacheTagsByCacheHash', 123)
        );
    }

    /**
     * @return void
     * @test
     */
    public function testGetCurrentCacheHash()
    {
        $tsfe = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->setMethods(['determineId'])
            ->disableOriginalConstructor()
            ->getMock();
        $tsfe->cHash = 123;
        $hook = $this->getMockBuilder(Frontend::class)
            ->setMethods(['getTsFe', 'getCacheTagsByCacheHash'])
            ->getMock();
        $hook
            ->expects($this->any())
            ->method('getTsFe')
            ->will($this->returnValue($tsfe));

        self::assertEquals(123, $this->callInaccessibleMethod($hook, 'getCurrentCacheHash'));

        $tsfe->newHash = 456;
        self::assertEquals(456, $this->callInaccessibleMethod($hook, 'getCurrentCacheHash'));
    }
}

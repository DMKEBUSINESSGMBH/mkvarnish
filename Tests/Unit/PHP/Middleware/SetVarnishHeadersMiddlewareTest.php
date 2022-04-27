<?php

namespace DMK\Mkvarnish\Tests\Unit\Middleware;

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

use DMK\Mkvarnish\Middleware\SetVarnishHeadersMiddleware;
use DMK\Mkvarnish\Repository\CacheTagsRepository;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Http\RequestHandler;

/**
 * This class communicates with the varnish server.
 *
 * @author Philipp Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class SetVarnishHeadersMiddlewareTest extends UnitTestCase
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
     * Test the handleHeaders method.
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testProcessWithHeaders()
    {
        $headers['name'] = 'wert';

        $middleware = $this->getMockBuilder(SetVarnishHeadersMiddleware::class)
            ->setMethods(['isSendCacheHeadersEnabled', 'getTsFe', 'getHeaders', 'addHeadersToResponse'])
            ->getMock();

        $request = $this->getMockBuilder(ServerRequest::class)->disableOriginalConstructor()->getMock();
        $handler = $this->getMockBuilder(RequestHandler::class)
            ->setMethods(['handle'])
            ->disableOriginalConstructor()
            ->getMock();
        $response = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();

        $middleware->expects($this->once())->method('getHeaders')->will($this->returnValue($headers));
        $handler->expects($this->once())->method('handle')->willReturn($response);
        $middleware->expects($this->once())->method('addHeadersToResponse')->with($response, $headers);

        $middleware->process($request, $handler);
    }

    /**
     * Test the handleHeaders method.
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testProcessWithoutHeaders()
    {
        $middleware = $this->getMockBuilder(SetVarnishHeadersMiddleware::class)
            ->setMethods(['isSendCacheHeadersEnabled', 'getTsFe', 'getHeaders', 'addHeadersToResponse'])
            ->getMock();

        $request = $this->getMockBuilder(ServerRequest::class)->disableOriginalConstructor()->getMock();
        $handler = $this->getMockBuilder(RequestHandler::class)->disableOriginalConstructor()->getMock();

        $middleware->expects($this->once())->method('getHeaders')->will($this->returnValue([]));
        $middleware->expects($this->never())->method('addHeadersToResponse');

        $middleware->process($request, $handler);
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
        $mock = $this->getMockBuilder(SetVarnishHeadersMiddleware::class)
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
        $tsfe = new \stdClass();
        $tsfe->newHash = 'asd123hjk678';
        $tsfe->config['INTincScript'] = ['one', 'two'];

        $mock = $this->getMockBuilder(SetVarnishHeadersMiddleware::class)
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

        $hook = $this->getMockBuilder(SetVarnishHeadersMiddleware::class)
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
        $hook = $this->getMockBuilder(SetVarnishHeadersMiddleware::class)
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
            $this->callInaccessibleMethod(new SetVarnishHeadersMiddleware(), 'getCacheTagsRepository')
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
            ->expects(self::once())
            ->method('deleteByCacheHash')
            ->with(123);

        $cacheTagsRepository
            ->expects(self::exactly(2))
            ->method('insertByTagAndCacheHash')
            ->withConsecutive(['tag_1', 123], ['tag_2', 123]);

        $hook = $this->getMockBuilder(SetVarnishHeadersMiddleware::class)
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

        $hook = $this->getMockBuilder(SetVarnishHeadersMiddleware::class)
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
        $hook = $this->getMockBuilder(SetVarnishHeadersMiddleware::class)
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

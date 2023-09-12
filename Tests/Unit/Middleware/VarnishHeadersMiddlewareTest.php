<?php

namespace DMK\Mkvarnish\Tests\Unit\Middleware;

use DMK\Mkvarnish\Middleware\VarnishHeadersMiddleware;
use DMK\Mkvarnish\Utility\Headers;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Http\RequestHandler;

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
 * This class communicates with the varnish server.
 *
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class VarnishHeadersMiddlewareTest extends UnitTestCase
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
     *
     * @test
     */
    public function testProcessWithHeaders()
    {
        $headers['name'] = 'wert';

        $headersUtility = $this->getMockBuilder(Headers::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $middleware = $this->getMockBuilder(VarnishHeadersMiddleware::class)
            ->setMethods(['addHeadersToResponse'])
            ->setConstructorArgs([$headersUtility])
            ->getMock();

        $request = $this->getMockBuilder(ServerRequest::class)->disableOriginalConstructor()->getMock();
        $handler = $this->getMockBuilder(RequestHandler::class)
            ->setMethods(['handle'])
            ->disableOriginalConstructor()
            ->getMock();
        $response = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();

        $headersUtility->expects($this->once())->method('get')->will($this->returnValue($headers));
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
     *
     * @test
     */
    public function testProcessWithoutHeaders()
    {
        $headersUtility = $this->getMockBuilder(Headers::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $middleware = $this->getMockBuilder(VarnishHeadersMiddleware::class)
            ->setMethods(['addHeadersToResponse'])
            ->setConstructorArgs([$headersUtility])
            ->getMock();

        $request = $this->getMockBuilder(ServerRequest::class)->disableOriginalConstructor()->getMock();
        $handler = $this->getMockBuilder(RequestHandler::class)->disableOriginalConstructor()->getMock();

        $headersUtility->expects($this->once())->method('get')->will($this->returnValue([]));
        $middleware->expects($this->never())->method('addHeadersToResponse');

        $middleware->process($request, $handler);
    }
}

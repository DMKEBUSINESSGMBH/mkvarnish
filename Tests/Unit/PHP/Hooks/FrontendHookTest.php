<?php
namespace DMK\Mkvarnish\Tests\Hooks;

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

use DMK\Mkvarnish\Hook\FrontendHook;

/**
 * This class communicates with the varnish server
 *
 * @package TYPO3
 * @subpackage DMK\Mkvarnish
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class FrontendHookTest extends \tx_rnbase_tests_BaseTestCase
{
    /**
     * Test the handleHeaders method
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testHandleHeadersWithHeaders()
    {
        $headers['name'] = 'wert';

        $mock = $this->getMock(
            FrontendHook::class,
            ['isSendCacheHeadersEnabled', 'getTsFe', 'getHeaders', 'sendHeaders']
        );

        $mock->expects($this->once())->method('getHeaders')->will($this->returnValue($headers));
        $mock->expects($this->once())->method('sendHeaders')->with($headers);

        $mock->handleHeaders();
    }

    /**
     * Test the handleHeaders method
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testHandleHeadersWithoutHeaders()
    {
        $mock = $this->getMock(
            FrontendHook::class,
            ['isSendCacheHeadersEnabled', 'getTsFe', 'getHeaders', 'sendHeaders']
        );

        $mock->expects($this->once())->method('getHeaders')->will($this->returnValue([]));
        $mock->expects($this->never())->method('sendHeaders');

        $mock->handleHeaders();
    }

    /**
     * Test the getHeaders method
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testGetHeadersWithoutVarnish()
    {
        $mock = $this->getMock(
            FrontendHook::class,
            ['isSendCacheHeadersEnabled']
        );

        $mock->expects($this->once())->method('isSendCacheHeadersEnabled')->will($this->returnValue(false));

        $headers = $this->callInaccessibleMethod(
            $mock,
            'getHeaders'
        );

        $this->assertTrue(is_array($headers));
        $this->assertEmpty($headers);
    }

    /**
     * Test the getHeaders method
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

        $mock = $this->getMock(
            FrontendHook::class,
            ['isSendCacheHeadersEnabled', 'getTsFe', 'getCacheTags', 'getSitename']
        );

        $mock->expects($this->once())->method('isSendCacheHeadersEnabled')->will($this->returnValue(true));
        $mock->expects($this->once())->method('getTsFe')->will($this->returnValue($tsfe));
        $mock->expects($this->once())->method('getSitename')->will($this->returnValue('345dfg'));
        ($mock
            ->expects($this->once())
            ->method('getCacheTags')
            ->will($this->returnValue(['pages', 'pages_419']))
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
}

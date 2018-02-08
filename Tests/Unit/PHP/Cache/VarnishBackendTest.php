<?php
namespace DMK\Mkvarnish\Tests\Unit\Hooks;

use DMK\Mkvarnish\Cache\VarnishBackend;
use DMK\Mkvarnish\Utility\CurlQueue;

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
 * DMK\Mkvarnish\Tests\Unit\Hooks$VarnishBackendTest
 *
 * @package         TYPO3
 * @subpackage
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class VarnishBackendTest extends \tx_rnbase_tests_BaseTestCase
{

    /**
     * @var string
     */
    private $siteNameBackup;

    /**
     * @var array
     */
    private $extConfBackup = [];

    /**
     * {@inheritDoc}
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->siteNameBackup = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        $this->extConfBackup = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mkvarnish'];
    }

    /**
     * {@inheritDoc}
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function tearDown()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = $this->siteNameBackup;
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mkvarnish'] = $this->extConfBackup;
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage the varnish cache backend can only remove cache entries by tags or the complete cache at the moment
     */
    public function testThrowExceptionIfNotImplemented()
    {
        $this->callInaccessibleMethod($this->getVarnishBackendInstance(), 'throwExceptionIfNotImplemented');
    }

    /**
     * @dataProvider dataProviderUnimplementedMethods
     */
    public function testUnimplementedMethods($method, array $arguments)
    {
        $varnishBackend = $this->getMock(
            VarnishBackend::class,
            ['throwExceptionIfNotImplemented'],
            ['Testing'],
            '',
            false
        );

        $varnishBackend
            ->expects(self::once())
            ->method('throwExceptionIfNotImplemented');

        call_user_func_array([$varnishBackend, $method], $arguments);
    }

    /**
     * @return string[][]|string[][][]
     */
    public function dataProviderUnimplementedMethods()
    {
        return [
            'method set, line: ' . __LINE__ => ['set', ['test', []]],
            'method get, line: ' . __LINE__ => ['get', ['test']],
            'method remove, line: ' . __LINE__ => ['remove', ['test']],
            'method has, line: ' . __LINE__ => ['has', ['test']],
            'method findIdentifiersByTag, line: ' . __LINE__ => ['findIdentifiersByTag', ['test']],
        ];
    }

    /**
     * @return void
     */
    public function testGetHmacForSitename()
    {
        $varnishBackend = $this->getVarnishBackendInstance();
        $firstHmac = $this->callInaccessibleMethod($varnishBackend, 'getHmacForSitename');
        $secondHmac = $this->callInaccessibleMethod($varnishBackend, 'getHmacForSitename');

        self::assertSame($firstHmac, $secondHmac, 'hmac for sitename is not same in 2 calls');
        self::assertInternalType('string', $firstHmac, 'hmac is no string');
        self::assertGreaterThan(30, strlen($firstHmac), 'hmac is not at least 30 chars long');

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = 'test site mkvarnish';
        $hmacAfterSiteNameChanged = $this->callInaccessibleMethod($varnishBackend, 'getHmacForSitename');
        self::assertNotSame($firstHmac, $hmacAfterSiteNameChanged, 'hmac for different site names is not different');
    }

    /**
     * @return void
     */
    public function testConvertCacheTagForPurge()
    {
        $convertedCacheTagForPurge = $this->callInaccessibleMethod(
            [$this->getVarnishBackendInstance(), 'convertCacheTagForPurge'],
            ['tt_content_5']
        );

        self::assertEquals('(tt_content_5)(,.+)?$', $convertedCacheTagForPurge);
    }

    /**
     * @return void
     */
    public function testGetHostNamesForPurge()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mkvarnish'] = serialize([]);
        $varnishBackend = $this->getVarnishBackendInstance();
        self::assertContains(
            $_SERVER['HTTP_HOST'],
            $this->callInaccessibleMethod($varnishBackend, 'getHostNamesForPurge')
        );
    }

    /**
     * @return void
     */
    public function testGetCurlQueueUtility()
    {
        self::assertInstanceOf(
            CurlQueue::class,
            $this->callInaccessibleMethod($this->getVarnishBackendInstance(), 'getCurlQueueUtility')
        );
    }

    /**
     * @return void
     */
    public function testExecutePurge()
    {
        $varnishBackend = $this->getMock(
            VarnishBackend::class,
            ['getHmacForSitename', 'getCurlQueueUtility', 'getHostNamesForPurge'],
            ['Testing'],
            '',
            false
        );

        $varnishBackend
            ->expects(self::once())
            ->method('getHmacForSitename')
            ->will($this->returnValue('abc123'));

        $varnishBackend
            ->expects(self::once())
            ->method('getHostNamesForPurge')
            ->will($this->returnValue(['firstHost', 'secondHost']));

        $curlQueueUtility = $this->getMock(CurlQueue::class, ['addCommand']);
        $curlQueueUtility
            ->expects(self::at(0))
            ->method('addCommand')
            ->with(
                'PURGE',
                'firstHost',
                ['X-Varnish-Purge-All: 1', 'X-TYPO3-Sitename: abc123']
            )
            ->will($this->returnValue($curlQueueUtility));
        $curlQueueUtility
            ->expects(self::at(1))
            ->method('addCommand')
            ->with(
                'PURGE',
                'secondHost',
                ['X-Varnish-Purge-All: 1', 'X-TYPO3-Sitename: abc123']
            )
            ->will($this->returnValue($curlQueueUtility));

        $varnishBackend
            ->expects(self::once())
            ->method('getCurlQueueUtility')
            ->will($this->returnValue($curlQueueUtility));

        $this->callInaccessibleMethod($varnishBackend, 'executePurge', ['X-Varnish-Purge-All' => 1]);
    }

    /**
     * @return void
     */
    public function testFlush()
    {
        $varnishBackend = $this->getMock(
            VarnishBackend::class,
            ['executePurge'],
            ['Testing'],
            '',
            false
        );

        $varnishBackend
            ->expects(self::once())
            ->method('executePurge')
            ->with(['X-Varnish-Purge-All' => 1]);

        $varnishBackend->flush();
    }

    /**
     * @return void
     */
    public function testFlushByTag()
    {
        $varnishBackend = $this->getMock(
            VarnishBackend::class,
            ['executePurge', 'convertCacheTagForPurge'],
            ['Testing'],
            '',
            false
        );

        $varnishBackend
            ->expects(self::once())
            ->method('convertCacheTagForPurge')
            ->with('testTag')
            ->will($this->returnValue('convertedTag'));

        $varnishBackend
            ->expects(self::once())
            ->method('executePurge')
            ->with(['X-Cache-Tags' => 'convertedTag']);

        $varnishBackend->flushByTag('testTag');
    }

    /**
     * @return \DMK\Mkvarnish\Cache\VarnishBackend
     */
    private function getVarnishBackendInstance()
    {
        return new VarnishBackend('Testing');
    }
}

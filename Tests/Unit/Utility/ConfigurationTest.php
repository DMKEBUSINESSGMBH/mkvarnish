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

namespace DMK\Mkvarnish\Tests\Unit\Utility;

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

use DMK\Mkvarnish\Utility\Configuration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * This class communicates with the varnish server.
 *
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class ConfigurationTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $extConfBackup = [];

    protected function setUp(): void
    {
        $this->extConfBackup = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['mkvarnish'] ?? [];
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['mkvarnish'] = $this->extConfBackup;
        parent::tearDown();
    }

    public function testGetExtConfValue(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['mkvarnish'] = ['my_key' => 'my_value'];

        $mock = $this->getAccessibleMock(Configuration::class, ['isRevProxy']);

        // should return right value
        $this->assertEquals(
            'my_value',
            $mock->_call('getExtConfValue', 'my_key')
        );
        // should return null if there is no value
        $this->assertEquals(
            null,
            $mock->_call('getExtConfValue', 'no_key')
        );
    }

    public function testIsSendCacheHeadersEnabledChecksReverseProxy(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['mkvarnish'] = ['sendCacheHeaders' => '0'];

        $mock = $this->getMockBuilder(Configuration::class)
            ->onlyMethods(['isRevProxy'])
            ->getMock();

        $mock->expects($this->once())->method('isRevProxy')->willReturn('rp');

        // should return rp
        $this->assertEquals(
            'rp',
            $mock->isSendCacheHeadersEnabled()
        );
    }

    public function testIsSendCacheHeadersEnabledShouldReturnTrue(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['mkvarnish'] = ['sendCacheHeaders' => '1'];

        $mock = $this->getMockBuilder(Configuration::class)
            ->onlyMethods(['isRevProxy'])
            ->getMock();
        $mock->expects($this->never())->method('isRevProxy');

        // should return rp
        $this->assertTrue($mock->isSendCacheHeadersEnabled());
    }

    public function testIsSendCacheHeadersEnabledShouldReturnFalse(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['mkvarnish'] = ['sendCacheHeaders' => '2'];

        $mock = $this->getMockBuilder(Configuration::class)
            ->onlyMethods(['isRevProxy'])
            ->getMock();
        $mock->expects($this->never())->method('isRevProxy');

        // should return rp
        $this->assertFalse($mock->isSendCacheHeadersEnabled());
    }

    public function testGetHostNamesForPurgeIfConfigured(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['mkvarnish'] = ['hostnames' => '127.0.0.1, 192.168.0.1'];

        $mock = new Configuration();

        $hostnames = $mock->getHostNamesForPurge();

        $this->assertCount(2, $hostnames);
        $this->assertEquals('127.0.0.1', $hostnames[0]);
        $this->assertEquals('192.168.0.1', $hostnames[1]);
    }

    public function testGetHostNamesForPurgeIfNoneConfigured(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['mkvarnish'] = ['hostnames' => ''];
        $_SERVER['HTTP_HOST'] ??= '127.0.0.1';
        $mock = new Configuration();

        $hostnames = $mock->getHostNamesForPurge();

        $this->assertCount(1, $hostnames);
        $this->assertEquals($_SERVER['HTTP_HOST'], $hostnames[0]);
    }
}

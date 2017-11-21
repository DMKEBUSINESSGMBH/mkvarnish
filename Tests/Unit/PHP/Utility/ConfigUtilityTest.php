<?php
namespace DMK\Mkvarnish\Tests\Utility;

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

use \DMK\Mkvarnish\Utility\ConfigUtility;

/**
 * This class communicates with the varnish server
 *
 * @package TYPO3
 * @subpackage DMK\Mkvarnish
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class ConfigUtilityTest extends \tx_rnbase_tests_BaseTestCase
{
    protected $extConfBackup = null;

    /**
     * Set up the Test
     *
     * @return void
     */
    public function setUp()
    {
        $this->extConfBackup = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mkvarnish'];
    }
    /**
     * Tear down the Test
     *
     * @return void
     */
    public function tearDown()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mkvarnish'] = $this->extConfBackup;
    }

    /**
     * Test the getExtConfValue method
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testGetExtConfValue()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mkvarnish'] = serialize(
            ['my_key' => 'my_value']
        );

        $mock = $this->getMock(ConfigUtility::class);

        // should return right value
        $this->assertEquals(
            'my_value',
            $this->callInaccessibleMethod([$mock, 'getExtConfValue'], ['my_key'])
        );
        // should return null if there is no value
        $this->assertEquals(
            null,
            $this->callInaccessibleMethod([$mock, 'getExtConfValue'], ['no_key'])
        );
    }

    /**
     * Test the isSendCacheHeadersEnabled method
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testIsSendCacheHeadersEnabledChecksReverseProxy()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mkvarnish'] = serialize(
            ['sendCacheHeaders' => '0']
        );

        $mock = $this->getMock(
            ConfigUtility::class,
            ['isRevProxy']
        );

        $mock->expects($this->once())->method('isRevProxy')->will($this->returnValue('rp'));

        // should return rp
        $this->assertEquals(
            'rp',
            $mock->isSendCacheHeadersEnabled()
        );
    }

    /**
     * Test the isSendCacheHeadersEnabled method
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testIsSendCacheHeadersEnabledShouldReturnTrue()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mkvarnish'] = serialize(
            ['sendCacheHeaders' => '1']
        );

        $mock = $this->getMock(
            ConfigUtility::class,
            ['isRevProxy']
        );
        $mock->expects($this->never())->method('isRevProxy');

        // should return rp
        $this->assertTrue($mock->isSendCacheHeadersEnabled());
    }

    /**
     * Test the isSendCacheHeadersEnabled method
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testIsSendCacheHeadersEnabledShouldReturnFalse()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mkvarnish'] = serialize(
            ['sendCacheHeaders' => '2']
        );

        $mock = $this->getMock(
            ConfigUtility::class,
            ['isRevProxy']
        );
        $mock->expects($this->never())->method('isRevProxy');

        // should return rp
        $this->assertFalse($mock->isSendCacheHeadersEnabled());
    }
}

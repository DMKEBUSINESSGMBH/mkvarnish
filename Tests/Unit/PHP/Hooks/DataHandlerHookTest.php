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

use DMK\Mkvarnish\Hook\DataHandlerHook;

/**
 * This class communicates with the varnish server
 *
 * @package TYPO3
 * @subpackage DMK\Mkvarnish
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class DataHandlerHookTest extends \tx_rnbase_tests_BaseTestCase
{
    /**
     * Test the clearCachePostProc method
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testClearCachePostProcForCacheCmdSystem()
    {
        $headers = [];
        $headers['X-TYPO3-Sitename'] = 'sda12367';
        $headers['X-Varnish-Purge-All'] = '1';

        $mock = $this->getMock(
            DataHandlerHook::class,
            ['isSendCacheHeadersEnabled', 'getSitename', 'executePurge']
        );

        $mock->expects($this->once())->method('isSendCacheHeadersEnabled')->will($this->returnValue(true));
        $mock->expects($this->once())->method('getSitename')->will($this->returnValue('sda12367'));
        $mock->expects($this->once())->method('executePurge')->with($headers);

        $mock->clearCachePostProc(['cacheCmd' => 'system']);
    }

    /**
     * Test the clearCachePostProc method
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testClearCachePostProcForTtContent()
    {
        $headers = [];
        $headers['X-TYPO3-Sitename'] = 'sda12367';
        $headers['X-Cache-Tags'] = ['tt_content', 'tt_content_5', 'pageId_7'];

        $mock = $this->getMock(
            DataHandlerHook::class,
            ['isSendCacheHeadersEnabled', 'getSitename', 'executePurge', 'convertCacheTagsForPurge']
        );

        $mock->expects($this->once())->method('isSendCacheHeadersEnabled')->will($this->returnValue(true));
        $mock->expects($this->once())->method('getSitename')->will($this->returnValue('sda12367'));
        $mock->expects($this->once())->method('convertCacheTagsForPurge')->will($this->returnArgument(0));
        $mock->expects($this->once())->method('executePurge')->with($headers);

        $mock->clearCachePostProc(['table' => 'tt_content', 'uid' => '5', 'uid_page' => '7']);
    }

    /**
     * Test the convertCacheTagsForPurge method
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testConvertCacheTagsForPurge()
    {
        $tags = ['tt_content', 'tt_content_5', 'pageId_7'];

        $mock = $this->getMock(
            DataHandlerHook::class
        );

        $converted = $this->callInaccessibleMethod(
            [$mock, 'convertCacheTagsForPurge'],
            [$tags]
        );

        // check if the right regex was returned
        $this->assertEquals('(tt_content|tt_content_5|pageId_7)(,.+)?$', $converted);
    }
}

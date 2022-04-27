<?php

namespace DMK\Mkvarnish\Tests\Unit;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

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
 * DMK\Mkvarnish\Tests\Unit\$MkVarnishBaseTest.
 *
 * @author          Philipp Wagner <philipp.wagner@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class MkVarnishBaseTestCase extends UnitTestCase
{
    protected function setExtensionConfiguration($value)
    {
        $extConf = GeneralUtility::makeInstance(
            ExtensionConfiguration::class
        );

        $typo3Version = VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getNumericTypo3Version());

        if ($typo3Version < 11000000) {
            $extConf->set('mkvarnish', '', $value);
        } else {
            $extConf->set('mkvarnish', $value);
        }
    }
}

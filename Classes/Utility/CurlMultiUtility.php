<?php
namespace DMK\Mkvarnish\Utility;

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
 * This class communicates with the varnish server
 *
 * @package TYPO3
 * @subpackage DMK\Mkvarnish
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class CurlMultiUtility
{
    /**
     * Queue ressource for curl multi-handle
     *
     * @var Resource
     */

    protected $curlQueue;
    /**
     * Queue for curl child handle
     *
     * @var array
     */
    protected $curlHandles;

    /**
     * Creates an instance of this utility
     *
     * @return \DMK\Mkvarnish\Utility\CurlMultiUtility
     */
    public static function instance()
    {
        static $instance;

        if ($instance === null) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Class constructor
     *
     * @throws \RuntimeException The Exception
     */
    public function __construct()
    {
        // check whether the cURL PHP Extension is loaded
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('The cURL PHP Extension is required by ext_varnish.');
        }

        // initialize cURL Multi-Handle Queue
        $this->curlQueue = curl_multi_init();
        $this->curlHandles = array();
    }

    /**
     * Add command to curl multi-handle queue
     *
     * @param string $method
     * @param string $url
     * @param array $header
     *
     * @return void
     */
    public function addCommand($method, $url, array $header)
    {
        // create Handle and at it to the Multi-Handle Queue
        $curlHandle = curl_init();
        $curlOptions = array (
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_TIMEOUT => 1,
            CURLOPT_RETURNTRANSFER => 1,
        );

        curl_setopt_array($curlHandle, $curlOptions);
        curl_multi_add_handle($this->curlQueue, $curlHandle);
        $this->curlHandles[] = $curlHandle;
    }


    /**
     * Class destructor
     *
     * @return void
     */
    public function __destruct()
    {
        // execute cURL Multi-Handle Queue
        $this->runQueue();
    }


    /**
     * Execute curl multi-handle queue
     *
     * @return void
     */
    protected function runQueue()
    {
        $running = null;
        do {
            curl_multi_exec($this->curlQueue, $running);
        } while ($running);

        // destroy all the handles
        foreach ($this->curlHandles as $handle) {
            curl_multi_remove_handle($this->curlQueue, $handle);
        }
        // destroy Handle which is not required anymore
        curl_multi_close($this->curlQueue);
    }
}

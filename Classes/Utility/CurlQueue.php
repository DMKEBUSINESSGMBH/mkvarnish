<?php

namespace DMK\Mkvarnish\Utility;

use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
class CurlQueue implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Queue ressource for curl multi-handle.
     *
     * @var \CurlMultiHandle|false|resource
     */
    protected $curlQueue;

    /**
     * Queue for curl child handle.
     *
     * @var array
     */
    protected $curlHandles;

    /**
     * Class constructor.
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
        $this->curlHandles = [];
    }

    /**
     * Add command to curl multi-handle queue.
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
        $curlOptions = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_TIMEOUT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLINFO_HEADER_OUT => true,
        ];

        curl_setopt_array($curlHandle, $curlOptions);
        curl_multi_add_handle($this->curlQueue, $curlHandle);
        $this->curlHandles[] = $curlHandle;
    }

    /**
     * Class destructor.
     *
     * @return void
     */
    public function __destruct()
    {
        // execute cURL Multi-Handle Queue
        $this->runQueue();
    }

    /**
     * Execute curl multi-handle queue.
     *
     * @return void
     */
    protected function runQueue()
    {
        $running = null;
        do {
            $status = curl_multi_exec($this->curlQueue, $running);
            if ($status > 0) {
                GeneralUtility::makeInstance(LogManager::class)->getLogger('mkvarnish')->error(
                    'curl request failed. Check devlog for more information.',
                    [
                        'status' => $status,
                        'error message' => curl_multi_strerror($status),
                        'curl queue info' => curl_multi_info_read($this->curlQueue),
                        'curl queue content' => curl_multi_getcontent($this->curlQueue),
                    ]
                );
            }
        } while ($running);

        // destroy all the handles
        foreach ($this->curlHandles as $handle) {
            if (200 != curl_getinfo($handle, CURLINFO_HTTP_CODE)) {
                GeneralUtility::makeInstance(LogManager::class)->getLogger('mkvarnish')->error(
                    'curl request returned no 200 HTTP code. Check devlog for more information.',
                    ['handle' => curl_getinfo($handle)]
                );
            }
            curl_multi_remove_handle($this->curlQueue, $handle);
        }
        // destroy Handle which is not required anymore
        curl_multi_close($this->curlQueue);
    }
}

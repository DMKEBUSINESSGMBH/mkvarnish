<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mkvarnish" Extension for TYPO3 CMS.
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

namespace DMK\Mkvarnish\Error\PageErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;

/***************************************************************
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
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
 * Class PageContentErrorHandler.
 *
 * The default page content error handler will create an internal subrequest if a TYPO3 page is configured as
 * error page which is usually the case. This subrequest will bypass Varnish by default. With this class no internal
 * subrequests are done.
 *
 * @author  Hannes Bochmann
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class PageContentErrorHandler extends \TYPO3\CMS\Core\Error\PageErrorHandler\PageContentErrorHandler
{
    /**
     * @SuppressWarnings("PHPMD.EmptyCatchBlock")
     */
    public function handlePageError(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        try {
            $urlParams = $this->link->resolve($this->errorHandlerConfiguration['errorContentSource']);
            $urlParams['pageuid'] = (int) ($urlParams['pageuid'] ?? 0);
            if (LinkService::TYPE_PAGE === ($urlParams['type'] ?? LinkService::TYPE_UNKNOWN)) {
                // If the resolved URL is written back to errorContentSource the URL will be resolved as external
                // request which means through varnish.
                $this->errorHandlerConfiguration['errorContentSource'] = $this->resolveUrl($request, $urlParams);
            }
        } catch (InvalidRouteArgumentsException|SiteNotFoundException) {
        }

        return parent::handlePageError($request, $message, $reasons);
    }
}

<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA
 */

namespace oat\taoLtiConsumer\model\result\parser\dataExtractor;

use DOMXPath;
use oat\taoLtiConsumer\model\result\ResultException;

interface DataExtractor
{
    /**
     * Check if the given xpath is acceptable for current DataExtractor
     *
     * @param DOMXPath $xpath
     * @return boolean
     */
    public function accepts(DOMXPath $xpath);

    /**
     * Get the request type of current DataExtractor
     *
     * @return string
     */
    public function getRequestType();

    /**
     * Get array of data extracted by DataExtractor
     *
     * @param DOMXPath $xpath
     * @return array
     * @throws ResultException If an error has occurred during parsing
     */
    public function getData(DOMXPath $xpath);
}
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
 * Copyright (c) 2019 (update and modification) Open Assessment Technologies SA
 */

namespace oat\taoLtiConsumer\model;

use Exception;

/**
 * Class ResultException
 * Stores optional data for further usage
 * @package oat\taoLtiConsumer\model
 */
class ResultException extends Exception
{
    /**
     * Optional data for further usage
     * @var mixed
     */
    private $optionalData;

    public function __construct($message = null, $code = 0, Exception $previous = null, $optionalData = array())
    {
        $this->optionalData = $optionalData;

        parent::__construct('', $code, $previous);

    }

    /**
     * @return mixed
     */
    public function getOptionalData()
    {
        return $this->optionalData;
    }
}
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

namespace oat\taoLtiConsumer\model\result;

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

    public function __construct($message = null, $code = 0, Exception $previous = null, $optionalData = [])
    {
        parent::__construct($message, $code, $previous);
        $this->optionalData = $optionalData;
    }

    /**
     * @return mixed
     */
    public function getOptionalData()
    {
        return $this->optionalData;
    }

    /**
     * Helper to create a ResultException from http code
     * - use MessageService to provide exception message & description
     *
     * @param int $code
     * @param Exception|null $previous
     * @return ResultException
     */
    static public function fromCode($code = MessagesService::STATUS_INTERNAL_SERVER_ERROR, Exception $previous = null)
    {
        if ($code == MessagesService::STATUS_METHOD_NOT_IMPLEMENTED) {
            return new self(
                MessagesService::$statuses[MessagesService::STATUS_METHOD_NOT_IMPLEMENTED],
                MessagesService::STATUS_METHOD_NOT_IMPLEMENTED,
                $previous,
                MessagesService::buildMessageData(MessagesService::STATUS_METHOD_NOT_IMPLEMENTED, [])
            );
        } else {
            return new self(
                MessagesService::$statuses[MessagesService::STATUS_INTERNAL_SERVER_ERROR],
                MessagesService::STATUS_INTERNAL_SERVER_ERROR,
                $previous,
                MessagesService::buildMessageData(MessagesService::STATUS_INTERNAL_SERVER_ERROR, [])
            );
        }
    }
}

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

class ResultException extends Exception
{
    /**
     * @var array
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
     * @param int            $code
     * @param Exception|null $previous
     *
     * @throws ResultException
     */
    public static function fromCode($code = MessageBuilder::STATUS_INTERNAL_SERVER_ERROR, Exception $previous = null)
    {
        $message = MessageBuilder::STATUSES[MessageBuilder::STATUS_INTERNAL_SERVER_ERROR];

        if (MessageBuilder::STATUS_METHOD_NOT_IMPLEMENTED === $code) {
            $message = MessageBuilder::STATUSES[MessageBuilder::STATUS_METHOD_NOT_IMPLEMENTED];
        }

        throw new self(
            $message,
            $code,
            $previous,
            MessageBuilder::build(MessageBuilder::STATUS_INTERNAL_SERVER_ERROR, [])
        );
    }
}

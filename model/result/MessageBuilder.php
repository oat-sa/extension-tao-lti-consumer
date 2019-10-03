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
namespace oat\taoLtiConsumer\model\result;

class MessageBuilder
{
    const FAILURE_MESSAGE = 'failure';
    const SUCCESS_MESSAGE = 'success';

    const STATUS_INVALID_SCORE = 400;
    const STATUS_DELIVERY_EXECUTION_NOT_FOUND = 404;
    const STATUS_METHOD_NOT_IMPLEMENTED = 501;
    const STATUS_INTERNAL_SERVER_ERROR = 500;
    const STATUS_SUCCESS = 201;

    const STATUSES = [
        self::STATUS_INVALID_SCORE => 'Invalid score',
        self::STATUS_DELIVERY_EXECUTION_NOT_FOUND => 'DeliveryExecution not found',
        self::STATUS_METHOD_NOT_IMPLEMENTED => 'Method not implemented',
        self::STATUS_INTERNAL_SERVER_ERROR => 'Internal server error, please retry',
    ];

    /**
     * @param $code   int self::STATUS_* code
     * @param $result array an array with Delivery Execution ID, score, message ID
     *
     * @return array
     */
    public function buildMessageData($code, $result)
    {
        $message = self::FAILURE_MESSAGE;
        $description = isset(self::STATUSES[$code]) ? self::STATUSES[$code] : '';
        $sourcedId = isset($result['sourcedId']) ? $result['sourcedId'] : '';
        $messageIdentifier = isset($result['messageIdentifier']) ? $result['messageIdentifier'] : '';

        if ($code === self::STATUS_SUCCESS) {
            $sourcedId = $result['sourcedId'];
            $message = self::SUCCESS_MESSAGE;
            $description = str_replace(
                ['{{sourceId}}', '{{score}}'],
                [$sourcedId, $result['score']],
                XmlFormatterService::SCORE_DESCRIPTION_TEMPLATE
            );
        }

        return [
            XmlFormatterService::TEMPLATE_VAR_CODE_MAJOR => $message,
            XmlFormatterService::TEMPLATE_VAR_DESCRIPTION => $description,
            XmlFormatterService::TEMPLATE_VAR_MESSAGE_ID => $messageIdentifier,
            XmlFormatterService::TEMPLATE_VAR_MESSAGE_REF_IDENTIFIER => $sourcedId,
        ];
    }
}
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
    public const FAILURE_MESSAGE = 'failure';
    public const SUCCESS_MESSAGE = 'success';

    public const STATUS_INVALID_SCORE = 400;
    public const STATUS_DELIVERY_EXECUTION_NOT_FOUND = 404;
    public const STATUS_METHOD_NOT_IMPLEMENTED = 501;
    public const STATUS_INTERNAL_SERVER_ERROR = 500;
    public const STATUS_SUCCESS = 201;

    public const STATUSES = [
        self::STATUS_INVALID_SCORE => 'Invalid score',
        self::STATUS_DELIVERY_EXECUTION_NOT_FOUND => 'DeliveryExecution not found',
        self::STATUS_METHOD_NOT_IMPLEMENTED => 'Method not implemented',
        self::STATUS_INTERNAL_SERVER_ERROR => 'Internal server error, please retry',
    ];

    /**
     * @param string $code
     * @param array $result
     *
     * @return array
     */
    public function build($code, array $result)
    {
        $message = self::FAILURE_MESSAGE;
        $description = self::STATUSES[$code] ?? '';
        $sourcedId = $result['sourcedId'] ?? '';
        $messageIdentifier = $result['messageIdentifier'] ?? '';

        if ($code === self::STATUS_SUCCESS) {
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

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

namespace oat\taoLtiConsumer\test\unit\model\result\parser;

use oat\generis\test\TestCase;
use oat\taoLtiConsumer\model\result\MessagesService;
use oat\taoLtiConsumer\model\result\XmlFormatterService;

class MessagesServiceTest extends TestCase
{
    /**
     * @dataProvider templateInputData
     * @param $code
     * @param $data
     * @param $response
     */
    public function testBuildMessage($code, $data, $response)
    {
        $result = MessagesService::buildMessageData($code, $data);
        $this->assertEquals($response, $result);
    }

    public function templateInputData()
    {
        return [
            [
                MessagesService::STATUS_SUCCESS,
                ['sourcedId' => '1', 'messageIdentifier' => '1', 'score' => '0.92'],
                [
                    XmlFormatterService::TEMPLATE_VAR_CODE_MAJOR => 'success',
                    XmlFormatterService::TEMPLATE_VAR_DESCRIPTION => 'Score for 1 is now 0.92',
                    XmlFormatterService::TEMPLATE_VAR_MESSAGE_ID => '1',
                    XmlFormatterService::TEMPLATE_VAR_MESSAGE_REF_IDENTIFIER => '1',
                ],
            ],
            [
                MessagesService::STATUS_INTERNAL_SERVER_ERROR,
                [],
                [
                    XmlFormatterService::TEMPLATE_VAR_CODE_MAJOR => 'failure',
                    XmlFormatterService::TEMPLATE_VAR_DESCRIPTION => MessagesService::STATUSES[MessagesService::STATUS_INTERNAL_SERVER_ERROR],
                    XmlFormatterService::TEMPLATE_VAR_MESSAGE_ID => '',
                    XmlFormatterService::TEMPLATE_VAR_MESSAGE_REF_IDENTIFIER => '',
                ],
            ],
            [
                MessagesService::STATUS_INVALID_SCORE,
                ['score' => 'Wrong Score'],
                [
                    XmlFormatterService::TEMPLATE_VAR_CODE_MAJOR => 'failure',
                    XmlFormatterService::TEMPLATE_VAR_DESCRIPTION => MessagesService::STATUSES[MessagesService::STATUS_INVALID_SCORE],
                    XmlFormatterService::TEMPLATE_VAR_MESSAGE_ID => '',
                    XmlFormatterService::TEMPLATE_VAR_MESSAGE_REF_IDENTIFIER => '',
                ],
            ],
            [
                MessagesService::STATUS_DELIVERY_EXECUTION_NOT_FOUND,
                ['score' => 'Wrong Score'],
                [
                    XmlFormatterService::TEMPLATE_VAR_CODE_MAJOR => 'failure',
                    XmlFormatterService::TEMPLATE_VAR_DESCRIPTION => MessagesService::STATUSES[MessagesService::STATUS_DELIVERY_EXECUTION_NOT_FOUND],
                    XmlFormatterService::TEMPLATE_VAR_MESSAGE_ID => '',
                    XmlFormatterService::TEMPLATE_VAR_MESSAGE_REF_IDENTIFIER => '',
                ],
            ],
            [
                MessagesService::STATUS_METHOD_NOT_IMPLEMENTED,
                ['score' => 'Wrong Score'],
                [
                    XmlFormatterService::TEMPLATE_VAR_CODE_MAJOR => 'failure',
                    XmlFormatterService::TEMPLATE_VAR_DESCRIPTION => MessagesService::STATUSES[MessagesService::STATUS_METHOD_NOT_IMPLEMENTED],
                    XmlFormatterService::TEMPLATE_VAR_MESSAGE_ID => '',
                    XmlFormatterService::TEMPLATE_VAR_MESSAGE_REF_IDENTIFIER => '',
                ],
            ],
        ];
    }
}

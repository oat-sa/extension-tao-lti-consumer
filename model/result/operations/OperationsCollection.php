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
 * Copyright (c) 2019-2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoLtiConsumer\model\result\operations;

use oat\oatbox\service\ConfigurableService;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseInterface;
use oat\taoLtiConsumer\model\result\operations\failure\BasicResponseSerializer;
use oat\taoLtiConsumer\model\result\operations\failure\Response as FailureResponse;
use oat\taoLtiConsumer\model\result\operations\failure\ResponseSerializer as FailureResponseSerializer;
use oat\taoLtiConsumer\model\result\operations\replace\OperationRequestParser as ReplaceOperationRequestParser;
use oat\taoLtiConsumer\model\result\operations\replace\Response as ReplaceResponse;
use oat\taoLtiConsumer\model\result\operations\replace\ResponseSerializer as ReplaceResponseSerializer;

class OperationsCollection extends ConfigurableService
{
    protected const OPERATION_REPLACE = 'replaceResultRequest';

    protected const KEY_RESPONSE_BODY_EL = 'response_body_el';
    protected const KEY_REQUEST_PARSER = 'req_parser';
    protected const KEY_RESPONSE_CLASS = 'response_class';
    protected const KEY_RESPONSE_SERIALIZER = 'response_serializer';

    public function getOperationRequestParser(string $operationName): ?OperationRequestParserInterface
    {
        $ops = $this->getSupportedOperations();
        return isset($ops[$operationName])
            ? $this->getServiceLocator()->get($ops[$operationName][self::KEY_REQUEST_PARSER])
            : null;
    }

    public function getBodyResponseElementName(string $operationName): ?string
    {
        $ops = $this->getSupportedOperations();
        return $ops[$operationName]
            ? $ops[$operationName][self::KEY_RESPONSE_BODY_EL]
            : null;
    }

    public function getResponseSerializer(LisOutcomeResponseInterface $response): ?ResponseSerializerInterface
    {
        $ops = $this->getSupportedOperations();
        array_push($ops, ...$this->getCommonResponseSerializationInfo());

        foreach ($ops as $op) {
            if (is_a($response, $op[self::KEY_RESPONSE_CLASS], true)) {
                /** @noinspection PhpIncompatibleReturnTypeInspection */
                return $this->getServiceLocator()->get($op[self::KEY_RESPONSE_SERIALIZER]);
            }
        }

        return null;
    }

    /**
     * @return string[][]
     */
    protected function getSupportedOperations(): array
    {
        return [
            self::OPERATION_REPLACE => [
                self::KEY_RESPONSE_BODY_EL => ReplaceResponseSerializer::BODY_RESPONSE_ELEMENT_NAME,
                self::KEY_REQUEST_PARSER => ReplaceOperationRequestParser::class,
                self::KEY_RESPONSE_CLASS => ReplaceResponse::class,
                self::KEY_RESPONSE_SERIALIZER => ReplaceResponseSerializer::class
            ]
        ];
    }

    /**
     * @return string[][]
     */
    protected function getCommonResponseSerializationInfo(): array
    {
        return [
            [
                self::KEY_RESPONSE_BODY_EL => null,
                self::KEY_REQUEST_PARSER => null,
                self::KEY_RESPONSE_CLASS => FailureResponse::class,
                self::KEY_RESPONSE_SERIALIZER => FailureResponseSerializer::class
            ],
            // Order matters, BasicResponse record should be the last one because in the opposite
            // situation any response pass 'is_a()' check first
            [
                self::KEY_RESPONSE_BODY_EL => null,
                self::KEY_REQUEST_PARSER => null,
                self::KEY_RESPONSE_CLASS => BasicResponse::class,
                self::KEY_RESPONSE_SERIALIZER => BasicResponseSerializer::class
            ]
        ];
    }
}

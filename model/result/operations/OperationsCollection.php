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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoLtiConsumer\model\result\operations;

use oat\oatbox\service\ConfigurableService;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseInterface;
use oat\taoLtiConsumer\model\result\operations\replace\OperationRequestParser as ReplaceOperationRequestParser;
use oat\taoLtiConsumer\model\result\operations\replace\Response as ReplaceResponse;
use oat\taoLtiConsumer\model\result\operations\replace\ResponseSerializer as ReplaceResponseSerializer;
use oat\taoLtiConsumer\model\result\operations\unsupported\Response as UnsupportedResponse;

class OperationsCollection extends ConfigurableService
{
    protected const OPERATION_REPLACE = 'replaceResultRequest';
    protected const OPERATION_READ = 'readResultRequest';
    protected const OPERATION_DELETE = 'deleteResultRequest';

    protected const KEY_REQUEST_PARSER = 'req_parser';
    protected const KEY_RESPONSE_CLASS = 'response_class';
    protected const KEY_RESPONSE_SERIALIZER = 'response_serializer';

    /**
     * @param string $operationName
     * @return OperationRequestParserInterface|null
     */
    public function getOperationRequestParser($operationName)
    {
        $ops = $this->getSupportedOperations();
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return isset($ops[$operationName])
            ? $this->getServiceLocator()->get($ops[$operationName][self::KEY_REQUEST_PARSER])
            : null;
    }

    /**
     * @param LisOutcomeResponseInterface $response
     * @return ResponseSerializerInterface|null
     */
    public function getResponseSerializer($response)
    {
        $ops = $this->getSupportedOperations();
        $ops[] = $this->getUnsupportedOperation();

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
    protected function getSupportedOperations()
    {
        return [
            self::OPERATION_REPLACE => [
                self::KEY_REQUEST_PARSER => ReplaceOperationRequestParser::class,
                self::KEY_RESPONSE_CLASS => ReplaceResponse::class,
                self::KEY_RESPONSE_SERIALIZER => ReplaceResponseSerializer::class
            ]
        ];
    }

    /**
     * @return string[]
     */
    protected function getUnsupportedOperation()
    {
        return [
            self::KEY_REQUEST_PARSER => null,
            self::KEY_RESPONSE_CLASS => UnsupportedResponse::class,
            self::KEY_RESPONSE_SERIALIZER => BasicResponseSerializer::class
        ];
    }
}

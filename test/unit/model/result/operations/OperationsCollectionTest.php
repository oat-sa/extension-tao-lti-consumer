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
namespace oat\taoLtiConsumer\test\unit\model\result\messages;

use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseInterface;
use oat\taoLtiConsumer\model\result\operations\BasicResponse;
use oat\taoLtiConsumer\model\result\operations\failure\BasicResponseSerializer;
use oat\taoLtiConsumer\model\result\operations\failure\Response as FailureResponse;
use oat\taoLtiConsumer\model\result\operations\OperationsCollection;
use oat\taoLtiConsumer\model\result\operations\replace\Response as ReplaceResponse;
use oat\taoLtiConsumer\model\result\operations\replace\OperationRequestParser as ReplaceOperationRequestParser;
use oat\taoLtiConsumer\model\result\operations\replace\ResponseSerializer as ReplaceResponseSerializer;
use oat\taoLtiConsumer\model\result\operations\failure\ResponseSerializer as FailureResponseSerializer;
use oat\taoLtiConsumer\model\result\operations\ResponseSerializerInterface;

class OperationsCollectionTest extends TestCase
{
    public function testGetOperationRequestParser()
    {
        $replaceOperationRequestParserMock = $this->createMock(ReplaceOperationRequestParser::class);

        $collection = new OperationsCollection();
        $collection->setServiceLocator($this->getServiceLocatorMock([
            ReplaceOperationRequestParser::class => $replaceOperationRequestParserMock
        ]));

        $parser = $collection->getOperationRequestParser('replaceResultRequest');
        $this->assertSame($parser, $replaceOperationRequestParserMock);

        $parser = $collection->getOperationRequestParser('readResultRequest');
        $this->assertNull($parser);

        $parser = $collection->getOperationRequestParser('deleteResultRequest');
        $this->assertNull($parser);

        $parser = $collection->getOperationRequestParser('unknown');
        $this->assertNull($parser);
    }

    public function testGetResponseSerializer()
    {
        $replaceSerializerMock = $this->createMock(ResponseSerializerInterface::class);
        $failureSerializerMock = $this->createMock(ResponseSerializerInterface::class);
        $basicSerializerMock = $this->createMock(ResponseSerializerInterface::class);

        $collection = new OperationsCollection();
        $collection->setServiceLocator($this->getServiceLocatorMock([
            ReplaceResponseSerializer::class => $replaceSerializerMock,
            FailureResponseSerializer::class => $failureSerializerMock,
            BasicResponseSerializer::class => $basicSerializerMock
        ]));

        /** @var ReplaceResponse|MockObject $responseMock */
        $responseMock = $this->createMock(ReplaceResponse::class);
        $result = $collection->getResponseSerializer($responseMock);
        $this->assertSame($replaceSerializerMock, $result);

        /** @var FailureResponse|MockObject $responseMock */
        $responseMock = $this->createMock(FailureResponse::class);
        $result = $collection->getResponseSerializer($responseMock);
        $this->assertSame($failureSerializerMock, $result);

        /** @var BasicResponse|MockObject $responseMock */
        $responseMock = $this->createMock(BasicResponse::class);
        $result = $collection->getResponseSerializer($responseMock);
        $this->assertSame($basicSerializerMock, $result);

        /** @var LisOutcomeResponseInterface|MockObject $responseMock */
        $responseMock = $this->createMock(LisOutcomeResponseInterface::class);
        $result = $collection->getResponseSerializer($responseMock);
        $this->assertNull($result);
    }

    public function testGetBodyResponseElementName()
    {
        $collection = new OperationsCollection();
        $this->assertSame(
            ReplaceResponseSerializer::BODY_RESPONSE_ELEMENT_NAME,
            $collection->getBodyResponseElementName('replaceResultRequest')
        );
        $this->assertNull($collection->getBodyResponseElementName('readResultRequest'));
        $this->assertNull($collection->getBodyResponseElementName('deleteResultRequest'));
        $this->assertNull($collection->getBodyResponseElementName('unknownRequest'));
    }
}

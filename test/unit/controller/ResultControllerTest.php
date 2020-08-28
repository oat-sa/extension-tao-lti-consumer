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

namespace oat\taoLtiConsumer\test\unit\controller;

use common_exception_MethodNotAllowed;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoLtiConsumer\controller\ResultController;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseInterface;
use oat\taoLtiConsumer\model\result\operations\failure\BasicResponseSerializer;
use oat\taoLtiConsumer\model\result\operations\OperationsCollection;
use oat\taoLtiConsumer\model\result\operations\replace\ReplaceResultOperationRequest;
use oat\taoLtiConsumer\model\result\operations\replace\Service\LtiReplaceResultParserProxy;
use oat\taoLtiConsumer\model\result\operations\ResponseSerializerInterface;
use oat\taoLtiConsumer\model\result\ResultService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\StatusCode;

class ResultControllerTest extends TestCase
{
    /** @var ResultController */
    private $subject;

    /** @var ResultService|MockObject */
    private $resultServiceMock;

    /** @var BasicResponseSerializer|MockObject */
    private $basicResponseSerializerMock;

    /** @var OperationsCollection|MockObject */
    private $operationsCollectionMock;

    /** @var LtiReplaceResultParserProxy|MockObject */
    private $ltiReplaceResultParserProxyMock;

    /** @var ServerRequestInterface|MockObject */
    private $requestMock;

    /** @var ReplaceResultOperationRequest */
    private $ReplaceResultOperationRequest;

    /** @var ResponseInterface|MockObject */
    private $responseMock;

    protected function setUp(): void
    {
        $this->subject = new ResultController();

        $this->resultServiceMock = $this->createMock(ResultService::class);
        $this->basicResponseSerializerMock = $this->createMock(BasicResponseSerializer::class);
        $this->operationsCollectionMock = $this->createMock(OperationsCollection::class);
        $this->ltiReplaceResultParserProxyMock = $this->createMock(LtiReplaceResultParserProxy::class);

        $this->requestMock = $this->createMock(ServerRequestInterface::class);
        $this->responseMock = $this->createMock(ResponseInterface::class);

        $this->ReplaceResultOperationRequest = $this->createMock(ReplaceResultOperationRequest::class);

        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    ResultService::class => $this->resultServiceMock,
                    BasicResponseSerializer::class => $this->basicResponseSerializerMock,
                    OperationsCollection::class => $this->operationsCollectionMock,
                    LtiReplaceResultParserProxy::class => $this->ltiReplaceResultParserProxyMock,
                ]
            )
        );

        $this->responseMock
            ->method('withHeader')
            ->with('Content-Type', ResultController::XML_CONTENT_TYPE)
            ->willReturn($this->responseMock);


        $this->subject->setRequest($this->requestMock);
        $this->subject->setResponse($this->responseMock);

    }

    public function testManageResults()
    {
        $this->requestMock->method('getMethod')->willReturn('POST');
        $this->requestMock->method('getBody')->willReturn('request_body');

        $this->ltiReplaceResultParserProxyMock
            ->expects($this->once())
            ->method('parse')
            ->willReturn($this->ReplaceResultOperationRequest);

        $lisResponseMock = $this->createMock(LisOutcomeResponseInterface::class);

        $lisResponseMock
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn(LisOutcomeResponseInterface::STATUS_SUCCESS);

        $this->resultServiceMock
            ->expects(self::once())
            ->method('process')
            ->willReturn($lisResponseMock);

        $responseSerializerMock = $this->createMock(ResponseSerializerInterface::class);

        $this->operationsCollectionMock
            ->expects($this->once())
            ->method('getResponseSerializer')
            ->willReturn($responseSerializerMock);

        $responseSerializerMock
            ->method('toXml')
            ->willReturn('string xml content');

        $this->responseMock
            ->expects($this->once())
            ->method('withStatus')
            ->with(StatusCode::HTTP_CREATED)
            ->willReturn($this->responseMock);

        $this->responseMock
            ->expects($this->once())
            ->method('withBody')
            ->willReturn($this->responseMock);

        $this->subject->manageResults();
    }

    public function testManageResultsEmptySerialiser(): void
    {
        $this->requestMock->method('getMethod')->willReturn('POST');
        $this->requestMock->method('getBody')->willReturn('request_body');

        $this->ltiReplaceResultParserProxyMock
            ->expects($this->once())
            ->method('parse')
            ->willReturn($this->ReplaceResultOperationRequest);

        $lisResponseMock = $this->createMock(LisOutcomeResponseInterface::class);

        $this->resultServiceMock
            ->expects(self::once())
            ->method('process')
            ->willReturn($lisResponseMock);

        $this->operationsCollectionMock
            ->expects($this->once())
            ->method('getResponseSerializer')
            ->willReturn(null);

        $this->basicResponseSerializerMock
            ->method('toXml')
            ->willReturn('some string');

        $this->responseMock
            ->expects($this->once())
            ->method('withStatus')
            ->with(StatusCode::HTTP_INTERNAL_SERVER_ERROR)
            ->willReturn($this->responseMock);

        $this->responseMock
            ->expects($this->once())
            ->method('withBody')
            ->willReturn($this->responseMock);

        $this->subject->manageResults();
    }


    public function testManageResultsGET(): void
    {
        $this->expectException(common_exception_MethodNotAllowed::class);

        $this->requestMock->method('getMethod')->willReturn('GET');
        $this->subject->manageResults();
    }
}

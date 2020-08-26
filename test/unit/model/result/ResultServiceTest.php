<?php

namespace oat\taoLtiConsumer\test\unit\model\result;

use common_exception_Error;
use common_exception_NotFound;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\event\EventManager;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\StateServiceInterface;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLtiConsumer\model\DeliveryExecutionGetterInterface;
use oat\taoLtiConsumer\model\result\event\LisScoreReceivedEvent;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequest;
use oat\taoLtiConsumer\model\result\operations\BasicResponse;
use oat\taoLtiConsumer\model\result\operations\failure\Response as FailureResponse;
use oat\taoLtiConsumer\model\result\operations\replace\ReplaceResultOperationRequest as ReplaceOperationRequest;
use oat\taoLtiConsumer\model\result\operations\replace\Response as ReplaceResponse;
use oat\taoLtiConsumer\model\result\ResultService;
use oat\taoLtiConsumer\model\result\ScoreWriterService;
use Psr\Log\LoggerInterface;

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

class ResultServiceTest extends TestCase
{
    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    public function testProcessReplaceRequest()
    {
        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->atLeastOnce())->method('info');

        /** @var EventManager|MockObject $eventManagerMock */
        $eventManagerMock = $this->createMock(EventManager::class);
        $eventManagerMock->expects($this->once())
            ->method('trigger')
            ->with($this->callback(function ($event) {
                return $event instanceof LisScoreReceivedEvent &&
                    $event->getDeliveryExecutionId() === 'de_id';
            }));

        /** @var LtiProvider|MockObject $ltiProviderMock */
        $ltiProviderMock = $this->createMock(LtiProvider::class);

        /** @var DeliveryExecutionInterface|MockObject $deliveryExecutionMock */
        $deliveryExecutionMock = $this->createMock(DeliveryExecution::class);
        $deliveryExecutionMock->method('getIdentifier')->willReturn('de_id');

        /** @var ScoreWriterService|MockObject $scoreWritterMock */
        $scoreWritterMock = $this->createMock(ScoreWriterService::class);
        $scoreWritterMock->expects($this->once())
            ->method('store')
            ->with($deliveryExecutionMock, '0.234')
            ->willReturn(true);

        /** @var DeliveryExecutionGetterInterface|MockObject $deGetterMock */
        $deGetterMock = $this->createMock(DeliveryExecutionGetterInterface::class);
        $deGetterMock->expects($this->once())
            ->method('get')
            ->with('de_id', $ltiProviderMock)
            ->willReturn($deliveryExecutionMock);

        /** @var DeliveryExecutionGetterInterface|MockObject $deGetterMock */
        $stateServiceMock = $this->createMock(StateServiceInterface::class);
        $deGetterMock->method('finish')
            ->with($deliveryExecutionMock)
            ->willReturn(true);

        /** @var ReplaceOperationRequest|MockObject $operationRequestMock */
        $operationRequestMock = $this->createMock(ReplaceOperationRequest::class);
        $operationRequestMock->method('getSourcedId')->willReturn('de_id');
        $operationRequestMock->method('getScore')->willReturn('0.234');

        /** @var LisOutcomeRequest|MockObject $requestMock */
        $requestMock = $this->createMock(LisOutcomeRequest::class);
        $requestMock->method('getOperation')->willReturn($operationRequestMock);
        $requestMock->method('getOperationName')->willReturn('replaceResultRequest');
        $requestMock->method('getMessageIdentifier')->willReturn('msg_identifier');

        $resultService = new ResultService();
        $resultService->setServiceLocator($this->getServiceLocatorMock([
            ScoreWriterService::class => $scoreWritterMock,
            DeliveryExecutionGetterInterface::SERVICE_ID => $deGetterMock,
            EventManager::SERVICE_ID => $eventManagerMock,
            StateServiceInterface::SERVICE_ID => $stateServiceMock
        ]));
        $resultService->setLogger($loggerMock);

        $response = $resultService->process($requestMock, $ltiProviderMock);
        $this->assertInstanceOf(ReplaceResponse::class, $response);
        $this->assertSame(ReplaceResponse::STATUS_SUCCESS, $response->getStatus());
        $this->assertSame(ReplaceResponse::CODE_MAJOR_SUCCESS, $response->getCodeMajor());
        $this->assertNotEmpty($response->getMessageIdentifier());
        $this->assertNotSame($response->getMessageIdentifier(), $response->getMessageRefIdentifier());
        $this->assertSame('msg_identifier', $response->getMessageRefIdentifier());
        $this->assertSame('replaceResultRequest', $response->getOperationRefIdentifier());
        $this->assertContains('de_id', $response->getStatusDescription());
        $this->assertContains('0.234', $response->getStatusDescription());
    }

    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    public function testProcessUnsupportedOperationRequest()
    {
        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->atLeastOnce())->method('warning');

        /** @var LtiProvider|MockObject $ltiProviderMock */
        $ltiProviderMock = $this->createMock(LtiProvider::class);

        /** @var LisOutcomeRequest|MockObject $requestMock */
        $requestMock = $this->createMock(LisOutcomeRequest::class);
        $requestMock->method('getOperation')->willReturn(null);
        $requestMock->method('getOperationName')->willReturn('unknownOne');
        $requestMock->method('getMessageIdentifier')->willReturn('msg_identifier');

        $resultService = new ResultService();
        $resultService->setLogger($loggerMock);

        $response = $resultService->process($requestMock, $ltiProviderMock);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(ReplaceResponse::STATUS_UNSUPPORTED, $response->getStatus());
        $this->assertSame(ReplaceResponse::CODE_MAJOR_UNSUPPORTED, $response->getCodeMajor());
        $this->assertNotEmpty($response->getMessageIdentifier());
        $this->assertNotSame($response->getMessageIdentifier(), $response->getMessageRefIdentifier());
        $this->assertSame('msg_identifier', $response->getMessageRefIdentifier());
        $this->assertSame('unknownOne', $response->getOperationRefIdentifier());
    }

    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    public function testDeliveryExecutionNotFound()
    {
        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->atLeastOnce())->method('warning');

        /** @var LtiProvider|MockObject $ltiProviderMock */
        $ltiProviderMock = $this->createMock(LtiProvider::class);

        /** @var DeliveryExecutionGetterInterface|MockObject $deGetterMock */
        $deGetterMock = $this->createMock(DeliveryExecutionGetterInterface::class);
        $deGetterMock->expects($this->once())
            ->method('get')
            ->with('de_id', $ltiProviderMock)
            ->willReturn(null);

        /** @var ReplaceOperationRequest|MockObject $operationRequestMock */
        $operationRequestMock = $this->createMock(ReplaceOperationRequest::class);
        $operationRequestMock->method('getSourcedId')->willReturn('de_id');
        $operationRequestMock->method('getScore')->willReturn('0.234');

        /** @var LisOutcomeRequest|MockObject $requestMock */
        $requestMock = $this->createMock(LisOutcomeRequest::class);
        $requestMock->method('getOperation')->willReturn($operationRequestMock);
        $requestMock->method('getOperationName')->willReturn('replaceResultRequest');
        $requestMock->method('getMessageIdentifier')->willReturn('msg_identifier');

        $resultService = new ResultService();
        $resultService->setServiceLocator($this->getServiceLocatorMock([
            DeliveryExecutionGetterInterface::SERVICE_ID => $deGetterMock,
        ]));
        $resultService->setLogger($loggerMock);

        $response = $resultService->process($requestMock, $ltiProviderMock);
        $this->assertInstanceOf(FailureResponse::class, $response);
        $this->assertSame(ReplaceResponse::STATUS_NOT_FOUND, $response->getStatus());
        $this->assertSame(ReplaceResponse::CODE_MAJOR_FAILURE, $response->getCodeMajor());
        $this->assertNotEmpty($response->getMessageIdentifier());
        $this->assertNotSame($response->getMessageIdentifier(), $response->getMessageRefIdentifier());
        $this->assertSame('msg_identifier', $response->getMessageRefIdentifier());
        $this->assertSame('replaceResultRequest', $response->getOperationRefIdentifier());
        $this->assertContains('de_id', $response->getStatusDescription());
    }
}

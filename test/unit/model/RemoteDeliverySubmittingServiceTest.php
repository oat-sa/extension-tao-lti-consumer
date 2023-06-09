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
 * Copyright (c) 2023 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoLtiConsumer\test\unit\model;

use core_kernel_classes_Resource;
use oat\taoLtiConsumer\model\RemoteDeliverySubmittingService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\DeliveryExecutionService;
use oat\tao\helpers\UrlHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

class RemoteDeliverySubmittingServiceTest extends TestCase
{
    private const EXECUTION_ID_QUERY_PARAM = 'execution';
    private const LTI_ERROR_MSG_QUERY_PARAM = 'lti_errormsg';
    private const LTI_ERROR_LOG_QUERY_PARAM = 'lti_errorlog';
    private const IRRECOVERABLE_ERROR_LOG_HINT = '[IRRECOVERABLE]';
    private const EXPECTED_EXECUTION_ID = 'execution_id';

    private RemoteDeliverySubmittingService $service;
    private UrlHelper $urlHelper;
    private DeliveryExecutionService $executionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->urlHelper = $this->createMock(UrlHelper::class);
        $this->executionService = $this->createMock(DeliveryExecutionService::class);

        $this->service = new RemoteDeliverySubmittingService($this->urlHelper, $this->executionService);
    }

    public function testProvideSubmitUrl()
    {
        $this->urlHelper->expects($this->once())
            ->method('buildUrl')
            ->with(
                'submitRemoteExecution',
                'ResultController',
                'taoLtiConsumer',
                [self::EXECUTION_ID_QUERY_PARAM => 'execution_id']
            )
            ->willReturn('http://example.com/submitRemoteExecution?execution=execution_id');

        $submitUrl = $this->service->provideSubmitUrl(self::EXPECTED_EXECUTION_ID);

        $this->assertEquals('http://example.com/submitRemoteExecution?execution=execution_id', $submitUrl);
    }

    public function testProvideSubmitUrlWithRuntimeError()
    {
        $this->urlHelper->expects($this->once())
            ->method('buildUrl')
            ->with(
                'submitRemoteExecution',
                'ResultController',
                'taoLtiConsumer',
                [self::EXECUTION_ID_QUERY_PARAM => 'execution_id']
            )
            ->willThrowException(new RuntimeException('Runtime error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Runtime error');

        $this->service->provideSubmitUrl(self::EXPECTED_EXECUTION_ID);
    }

    public function testSubmitRemoteExecutionWithMissingExecutionIdQueryParam()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Execution id is not provided');

        $this->service->submitRemoteExecution([]);
    }

    public function testSubmitRemoteExecutionWithTerminatedOrFinishedState()
    {
        $queryParams = [self::EXECUTION_ID_QUERY_PARAM => self::EXPECTED_EXECUTION_ID];

        $execution = $this->createMock(DeliveryExecutionInterface::class);
        $stateMock = $this->createMock(core_kernel_classes_Resource::class);
        $stateMock->expects($this->once())
            ->method('getUri')
            ->willReturn(DeliveryExecutionInterface::STATE_TERMINATED);
        $execution->expects($this->once())
            ->method('getState')
            ->willReturn($stateMock);

        $this->executionService->expects($this->once())
            ->method('getDeliveryExecution')
            ->with(self::EXPECTED_EXECUTION_ID)
            ->willReturn($execution);

        $this->service->submitRemoteExecution($queryParams);

        // No assertions needed as the method should return without throwing an exception
    }

    public function testSubmitRemoteExecutionWithoutErrorParams()
    {
        $queryParams = [self::EXECUTION_ID_QUERY_PARAM => self::EXPECTED_EXECUTION_ID];

        $execution = $this->createMock(DeliveryExecutionInterface::class);
        $stateMock = $this->createMock(core_kernel_classes_Resource::class);
        $stateMock->expects($this->once())
            ->method('getUri')
            ->willReturn(DeliveryExecutionInterface::STATE_ACTIVE);
        $execution->expects($this->once())
            ->method('getState')
            ->willReturn($stateMock);

        $execution->expects($this->once())
            ->method('setState')
            ->with(DeliveryExecutionInterface::STATE_FINISHED);

        $this->executionService->expects($this->once())
            ->method('getDeliveryExecution')
            ->with(self::EXPECTED_EXECUTION_ID)
            ->willReturn($execution);

        $this->service->submitRemoteExecution($queryParams);

        // No assertions needed as the method should return without throwing an exception
    }

    public function testSubmitRemoteExecutionWithIrrecoverableErrorLog()
    {
        $queryParams = [
            self::EXECUTION_ID_QUERY_PARAM => self::EXPECTED_EXECUTION_ID,
            self::LTI_ERROR_LOG_QUERY_PARAM => 'Some error log ' . self::IRRECOVERABLE_ERROR_LOG_HINT,
        ];

        $execution = $this->createMock(DeliveryExecutionInterface::class);
        $stateMock = $this->createMock(core_kernel_classes_Resource::class);
        $stateMock->expects($this->once())
            ->method('getUri')
            ->willReturn(DeliveryExecutionInterface::STATE_ACTIVE);
        $execution->expects($this->once())
            ->method('getState')
            ->willReturn($stateMock);

        $execution->expects($this->once())
            ->method('setState')
            ->with(DeliveryExecution::STATE_TERMINATED);

        $this->executionService->expects($this->once())
            ->method('getDeliveryExecution')
            ->with(self::EXPECTED_EXECUTION_ID)
            ->willReturn($execution);

        $this->service->submitRemoteExecution($queryParams);

        // No assertions needed as the method should return without throwing an exception
    }

    public function testSubmitRemoteExecutionWithThrownLtiMsg()
    {
        $queryParams = [
            self::EXECUTION_ID_QUERY_PARAM => self::EXPECTED_EXECUTION_ID,
            self::LTI_ERROR_LOG_QUERY_PARAM => 'Some error log ',
            self::LTI_ERROR_MSG_QUERY_PARAM => 'Some error message'
        ];

        $execution = $this->createMock(DeliveryExecutionInterface::class);
        $stateMock = $this->createMock(core_kernel_classes_Resource::class);
        $stateMock->expects($this->once())
            ->method('getUri')
            ->willReturn(DeliveryExecutionInterface::STATE_ACTIVE);
        $execution->expects($this->once())
            ->method('getState')
            ->willReturn($stateMock);

        $execution->expects($this->never())
            ->method('setState')
            ->with(DeliveryExecutionInterface::STATE_TERMINATED);

        $this->executionService->expects($this->once())
            ->method('getDeliveryExecution')
            ->with(self::EXPECTED_EXECUTION_ID)
            ->willReturn($execution);


        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Some error message');

        $this->service->submitRemoteExecution($queryParams);

        // No assertions needed as the method should return without throwing an exception
    }
}

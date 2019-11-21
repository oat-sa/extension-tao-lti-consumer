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

namespace oat\taoLtiConsumer\test\unit\model\result;

use common_exception_Error;
use common_exception_NotFound;
use core_kernel_classes_Resource;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLtiConsumer\model\result\ScoreWriterService;
use oat\taoResultServer\models\classes\ResultServerService;
use oat\taoResultServer\models\Exceptions\DuplicateVariableException;
use Psr\Log\LoggerInterface;
use taoResultServer_models_classes_OutcomeVariable;
use taoResultServer_models_classes_Variable;
use taoResultServer_models_classes_WritableResultStorage;

class ScoreWriterServiceTest extends TestCase
{
    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    public function testStore()
    {
        /** @var taoResultServer_models_classes_WritableResultStorage|MockObject $resultStorageMock */
        $resultStorageMock = $this->createMock(taoResultServer_models_classes_WritableResultStorage::class);
        $resultStorageMock->expects($this->once())
            ->method('storeTestVariable')
            ->with(
                'de_id',
                'd_uri',
                $this->callback(function (taoResultServer_models_classes_Variable $var) {
                    return
                        $var->getIdentifier() === 'SCORE' &&
                        $var->getCardinality() === taoResultServer_models_classes_OutcomeVariable::CARDINALITY_SINGLE &&
                        $var->getBaseType() === 'float' &&
                        $var->getValue() === '0.45';
                }),
                'de_id'
            );

        /** @var ResultServerService|MockObject $resultServiceMock */
        $resultServiceMock = $this->createMock(ResultServerService::class);
        $resultServiceMock->expects($this->once())
            ->method('getResultStorage')
            ->with('de_id')
            ->willReturn($resultStorageMock);

        /** @var core_kernel_classes_Resource|MockObject $deliveryMock */
        $deliveryMock = $this->createMock(core_kernel_classes_Resource::class);
        $deliveryMock->method('getUri')->willReturn('d_uri');

        /** @var DeliveryExecutionInterface|MockObject $deliveryExecutionMock */
        $deliveryExecutionMock = $this->createMock(DeliveryExecutionInterface::class);
        $deliveryExecutionMock->method('getIdentifier')->willReturn('de_id');
        $deliveryExecutionMock->method('getDelivery')->willReturn($deliveryMock);

        $scoreWriter = new ScoreWriterService();
        $scoreWriter->setServiceLocator($this->getServiceLocatorMock([
            ResultServerService::SERVICE_ID => $resultServiceMock
        ]));
        $result = $scoreWriter->store($deliveryExecutionMock, '0.45');
        $this->assertTrue($result);
    }

    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    public function testDuplicatedVariable()
    {
        /** @var taoResultServer_models_classes_WritableResultStorage|MockObject $resultStorageMock */
        $resultStorageMock = $this->createMock(taoResultServer_models_classes_WritableResultStorage::class);
        $resultStorageMock->expects($this->once())
            ->method('storeTestVariable')
            ->willThrowException(new DuplicateVariableException('mm'));

        /** @var ResultServerService|MockObject $resultServiceMock */
        $resultServiceMock = $this->createMock(ResultServerService::class);
        $resultServiceMock->expects($this->once())
            ->method('getResultStorage')
            ->with('de_id')
            ->willReturn($resultStorageMock);

        /** @var core_kernel_classes_Resource|MockObject $deliveryMock */
        $deliveryMock = $this->createMock(core_kernel_classes_Resource::class);
        $deliveryMock->method('getUri')->willReturn('d_uri');

        /** @var DeliveryExecutionInterface|MockObject $deliveryExecutionMock */
        $deliveryExecutionMock = $this->createMock(DeliveryExecutionInterface::class);
        $deliveryExecutionMock->method('getIdentifier')->willReturn('de_id');
        $deliveryExecutionMock->method('getDelivery')->willReturn($deliveryMock);

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->callback(static function ($message) {
                return strpos($message, 'de_id') !== false;
            }));

        $scoreWriter = new ScoreWriterService();
        $scoreWriter->setServiceLocator($this->getServiceLocatorMock([
            ResultServerService::SERVICE_ID => $resultServiceMock
        ]));
        $scoreWriter->setLogger($loggerMock);

        $result = $scoreWriter->store($deliveryExecutionMock, '0.45');
        $this->assertFalse($result);
    }
}

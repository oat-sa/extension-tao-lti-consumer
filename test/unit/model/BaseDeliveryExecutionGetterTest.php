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

namespace oat\taoLtiConsumer\test\unit\model;

use core_kernel_classes_Resource;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoDelivery\model\execution\KVDeliveryExecution;
use oat\taoDelivery\model\execution\OntologyDeliveryExecution;
use oat\taoDelivery\model\execution\rds\RdsDeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLtiConsumer\model\BaseDeliveryExecutionGetter;

class BaseDeliveryExecutionGetterTest extends TestCase
{
    public function testGetExistingRdf()
    {
        $deliveryExecutionMock = $this->createMock(OntologyDeliveryExecution::class);
        $deliveryExecutionMock->method('exists')->willReturn(true);

        $serviceProxyMock = $this->createMock(ServiceProxy::class);
        $serviceProxyMock->expects($this->once())
            ->method('getDeliveryExecution')
            ->with('de_id')
            ->willReturn($deliveryExecutionMock);

        $deGetter = new BaseDeliveryExecutionGetter();
        $deGetter->setServiceLocator($this->getServiceLocatorMock([
            ServiceProxy::SERVICE_ID => $serviceProxyMock
        ]));

        /** @var MockObject|LtiProvider $ltiProviderMock */
        $ltiProviderMock = $this->createMock(LtiProvider::class);
        $this->assertSame($deliveryExecutionMock, $deGetter->get('de_id', $ltiProviderMock));
    }

    public function testGetNotExistingRdf()
    {
        $deliveryExecutionMock = $this->createMock(OntologyDeliveryExecution::class);
        $deliveryExecutionMock->method('exists')->willReturn(false);

        $serviceProxyMock = $this->createMock(ServiceProxy::class);
        $serviceProxyMock->expects($this->once())
            ->method('getDeliveryExecution')
            ->with('de_id')
            ->willReturn($deliveryExecutionMock);

        $deGetter = new BaseDeliveryExecutionGetter();
        $deGetter->setServiceLocator($this->getServiceLocatorMock([
            ServiceProxy::SERVICE_ID => $serviceProxyMock
        ]));

        /** @var MockObject|LtiProvider $ltiProviderMock */
        $ltiProviderMock = $this->createMock(LtiProvider::class);
        $this->assertNull($deGetter->get('de_id', $ltiProviderMock));
    }

    public function testGetExistingKv()
    {
        $deliveryExecutionMock = $this->createMock(KVDeliveryExecution::class);
        $deliveryExecutionMock->expects($this->once())->method('exists')->willReturn(true);

        $serviceProxyMock = $this->createMock(ServiceProxy::class);
        $serviceProxyMock->expects($this->once())
            ->method('getDeliveryExecution')
            ->with('de_id')
            ->willReturn($deliveryExecutionMock);

        $deGetter = new BaseDeliveryExecutionGetter();
        $deGetter->setServiceLocator($this->getServiceLocatorMock([
            ServiceProxy::SERVICE_ID => $serviceProxyMock
        ]));

        /** @var MockObject|LtiProvider $ltiProviderMock */
        $ltiProviderMock = $this->createMock(LtiProvider::class);
        $this->assertSame($deliveryExecutionMock, $deGetter->get('de_id', $ltiProviderMock));
    }

    public function testGetNotExistingKv()
    {
        $deliveryExecutionMock = $this->createMock(KVDeliveryExecution::class);
        $deliveryExecutionMock->expects($this->once())->method('exists')->willReturn(false);

        $serviceProxyMock = $this->createMock(ServiceProxy::class);
        $serviceProxyMock->expects($this->once())
            ->method('getDeliveryExecution')
            ->with('de_id')
            ->willReturn($deliveryExecutionMock);

        $deGetter = new BaseDeliveryExecutionGetter();
        $deGetter->setServiceLocator($this->getServiceLocatorMock([
            ServiceProxy::SERVICE_ID => $serviceProxyMock
        ]));

        /** @var MockObject|LtiProvider $ltiProviderMock */
        $ltiProviderMock = $this->createMock(LtiProvider::class);
        $this->assertNull($deGetter->get('de_id', $ltiProviderMock));
    }

    public function testGetExistingRds()
    {
        $stateMock = $this->createMock(core_kernel_classes_Resource::class);
        $stateMock->expects($this->once())
            ->method('getUri')
            ->willReturn('urrri');

        $deliveryExecutionMock = $this->createMock(RdsDeliveryExecution::class);
        $deliveryExecutionMock->expects($this->once())
            ->method('getState')
            ->willReturn($stateMock);

        $serviceProxyMock = $this->createMock(ServiceProxy::class);
        $serviceProxyMock->expects($this->once())
            ->method('getDeliveryExecution')
            ->with('de_id')
            ->willReturn($deliveryExecutionMock);

        $deGetter = new BaseDeliveryExecutionGetter();
        $deGetter->setServiceLocator($this->getServiceLocatorMock([
            ServiceProxy::SERVICE_ID => $serviceProxyMock
        ]));

        /** @var MockObject|LtiProvider $ltiProviderMock */
        $ltiProviderMock = $this->createMock(LtiProvider::class);
        $this->assertSame($deliveryExecutionMock, $deGetter->get('de_id', $ltiProviderMock));
    }

    public function testGetNotExistingRds()
    {
        $deliveryExecutionMock = $this->createMock(RdsDeliveryExecution::class);
        $deliveryExecutionMock->expects($this->once())
            ->method('getState')
            ->willReturn(null);

        $serviceProxyMock = $this->createMock(ServiceProxy::class);
        $serviceProxyMock->expects($this->once())
            ->method('getDeliveryExecution')
            ->with('de_id')
            ->willReturn($deliveryExecutionMock);

        $deGetter = new BaseDeliveryExecutionGetter();
        $deGetter->setServiceLocator($this->getServiceLocatorMock([
            ServiceProxy::SERVICE_ID => $serviceProxyMock
        ]));

        /** @var MockObject|LtiProvider $ltiProviderMock */
        $ltiProviderMock = $this->createMock(LtiProvider::class);
        $this->assertNull($deGetter->get('de_id', $ltiProviderMock));
    }
}

<?php
/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2020 (original work) Open Assessment Technologies SA
 */

namespace oat\taoLtiConsumer\test\unit\model\Tool\Service;

use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\taoDeliverConnect\model\delivery\factory\RemoteDeliveryFactory;
use oat\taoDeliverConnect\model\TenantLtiProvider;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLtiConsumer\model\Tool\Service\ResourceLinkIdDiscover;
use PHPUnit\Framework\MockObject\MockObject;

class ResourceLinkIdDiscoverTest extends TestCase
{
    /** @var Ontology|MockObject */
    private $ontology;

    /** @var ResourceLinkIdDiscover */
    private $subject;

    public function setUp(): void
    {
        $this->ontology = $this->createMock(Ontology::class);
        $this->subject = new ResourceLinkIdDiscover();
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    Ontology::SERVICE_ID => $this->ontology
                ]
            )
        );
    }

    public function testDiscoverByDeliveryExecutionAndLtiProviderBasedOnRdfLtiProvider(): void
    {
        $ltiProvider = $this->createMock(LtiProvider::class);
        $execution = $this->createMock(DeliveryExecution::class);

        $execution->method('getIdentifier')
            ->willReturn('deliveryExecutionIdentifier');

        $this->assertSame(
            'deliveryExecutionIdentifier',
            $this->subject->discoverByDeliveryExecutionAndLtiProvider($execution, $ltiProvider)
        );
    }

    public function testDiscoverByDeliveryExecutionAndLtiProviderBasedOnTenantLtiProvider(): void
    {
        $ltiProvider = $this->createMock(TenantLtiProvider::class);
        $delivery = $this->createMock(core_kernel_classes_Resource::class);
        $publishDeliveryProperty = $this->createMock(core_kernel_classes_Property::class);
        $execution = $this->createMock(DeliveryExecution::class);

        $delivery->method('getUniquePropertyValue')
            ->willReturn('abc123');

        $execution->method('getDelivery')
            ->willReturn($delivery);

        $this->ontology
            ->method('getProperty')
            ->with(RemoteDeliveryFactory::PROPERTY_PUBLISHED_DELIVERY_ID)
            ->willReturn($publishDeliveryProperty);

        $this->assertSame(
            'abc123',
            $this->subject->discoverByDeliveryExecutionAndLtiProvider($execution, $ltiProvider)
        );
    }
}

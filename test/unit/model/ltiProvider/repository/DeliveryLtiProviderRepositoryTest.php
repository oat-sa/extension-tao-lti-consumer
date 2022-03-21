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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoLtiConsumer\test\unit\model\ltiProvider\repository;

use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;
use oat\taoLtiConsumer\model\delivery\lookup\DeliveryLookupByDeliveryExecution;
use oat\taoLtiConsumer\model\delivery\lookup\DeliveryLookupInterface;
use oat\taoLtiConsumer\model\ltiProvider\repository\DeliveryLtiProviderRepository;
use PHPUnit\Framework\MockObject\MockObject;

class DeliveryLtiProviderRepositoryTest extends TestCase
{
    /** @var DeliveryLtiProviderRepository */
    private $subject;

    /** @var LtiProviderService|MockObject */
    private $ltiProviderService;

    /** @var core_kernel_classes_Resource|MockObject */
    private $delivery;

    /** @var Ontology|MockObject */
    private $model;

    /** @var core_kernel_classes_Property|MockObject */
    private $deliveryProperty;

    /** @var LtiProvider|MockObject */
    private $ltiProvider;

    /** @var DeliveryLookupByDeliveryExecution|MockObject  */
    private $deliveryLookup;

    public function setUp(): void
    {
        $this->ltiProviderService = $this->createMock(LtiProviderService::class);
        $this->delivery = $this->createMock(core_kernel_classes_Resource::class);
        $this->deliveryProperty = $this->createMock(core_kernel_classes_Property::class);
        $this->model = $this->createMock(Ontology::class);
        $this->ltiProvider = $this->createMock(LtiProvider::class);
        $this->deliveryLookup = $this->createMock(DeliveryLookupInterface::class);

        $this->subject = new DeliveryLtiProviderRepository(
            $this->ltiProviderService,
            $this->model,
            [
                $this->deliveryLookup
            ]

        );
    }

    public function testSearchBySourcedId(): void
    {
        $this->deliveryLookup
            ->method('searchBySourcedId')
            ->willReturn($this->delivery);

        $this->model
            ->expects($this->once())
            ->method('getProperty')
            ->with('http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDeliveryContainer')
            ->willReturn($this->deliveryProperty);

        $this->delivery
            ->expects($this->once())
            ->method('getOnePropertyValue')
            ->willReturn($this->getDeliveryContainerProperty('id', 'path/to/smthwhere'));

        $this->ltiProviderService
            ->expects($this->once())
            ->method('searchById')
            ->willReturn($this->ltiProvider);

        $this->subject->searchBySourcedId('deliveryExecutionId');
    }

    public function testSearchBySourcedIdWithWrongProperty(): void
    {
        $this->expectException(LtiException::class);

        $this->deliveryLookup
            ->method('searchBySourcedId')
            ->willReturn($this->delivery);

        $this->model
            ->expects($this->once())
            ->method('getProperty')
            ->with('http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDeliveryContainer')
            ->willReturn($this->deliveryProperty);

        $this->delivery
            ->expects($this->once())
            ->method('getOnePropertyValue')
            ->willReturn($this->getInvalidDeliveryContainerProperty());

        $this->assertSame($this->ltiProvider, $this->subject->searchBySourcedId('deliveryExecutionId'));
    }

    private function getDeliveryContainerProperty(string $ltiProvider, string $ltiProviderPath): string
    {
        return sprintf(
            '{"container":"lti","params":{"ltiProvider":"%s","ltiPath":"%s"}}',
            $ltiProvider,
            $ltiProviderPath
        );
    }

    private function getInvalidDeliveryContainerProperty(): string
    {
        return '{"container":"lti"}';
    }
}

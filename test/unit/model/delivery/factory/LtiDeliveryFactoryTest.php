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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

declare(strict_types=1);

namespace oat\taoLtiConsumer\model\delivery\factory;

use common_ext_Extension as Extension;
use common_ext_ExtensionsManager as ExtensionsManager;
use common_report_Report as Report;
use core_kernel_classes_Class as RdfClass;
use core_kernel_classes_Resource as RdfResource;
use oat\generis\model\OntologyRdfs;
use oat\generis\test\TestCase;
use oat\oatbox\event\EventManager;
use oat\oatbox\log\LoggerService;
use oat\tao\model\taskQueue\QueueDispatcher;
use oat\taoDeliveryRdf\model\ContainerRuntime;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLtiConsumer\model\delivery\container\LtiDeliveryContainer;
use oat\taoLtiConsumer\model\delivery\task\LtiDeliveryCreationTask;
use phpmock\Mock;
use phpmock\MockBuilder;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Psr\Log\LoggerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class LtiDeliveryFactory
 *
 * A factory to create LTI based delivery, this creation can done in a deferred way
 *
 * @package oat\taoLtiConsumer\model\delivery\factory
 */
class LtiDeliveryFactoryTest extends TestCase
{
    const FIXED_TIME = 1234567890;
    const RESOURCE_URI = 'Uri of the resource';

    /** @var Mock */
    protected $timeMock;

    public function setUp(): void
    {
        define('CONFIG_PATH', ROOT_PATH . 'config/');
        $this->timeMock = $this->MockFunction('oat\taoLtiConsumer\model\delivery\factory', "time", self::FIXED_TIME);
        $this->timeMock->disableAll();
        $this->timeMock->enable();
    }

    public function tearDown(): void
    {
        $this->timeMock->disableAll();
    }

    /**
     * @param string           $initialLabel
     * @param string           $finalLabel
     * @param RdfResource|null $deliveryResource
     *
     * @throws \common_exception_InconsistentData
     * @throws \phpmock\MockEnabledException
     */
    public function launchGetLtiDeliveryContainerTest($initialLabel, $finalLabel, RdfResource $deliveryResource = null)
    {
        $id = 'providerId';
        $ltiPath = 'some path';
        $classLabel = 'label of the class';
        $ltiProviderLabel = 'label of the lti provider';

        /** @var RdfClass|MockObject $deliveryClass */
        $deliveryClass = $this->getMockBuilder(RdfClass::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLabel', 'countInstances', 'createInstanceWithProperties'])
            ->getMock();
        $deliveryClass->method('getLabel')->willReturn($classLabel);
        $deliveryClass->method('countInstances')->willReturn(0);

        /** @var LtiProvider|MockObject $ltiProvider */
        $ltiProvider = $this->getMockBuilder(LtiProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getLabel'])
            ->getMock();
        $ltiProvider->method('getId')->willReturn($id);
        $ltiProvider->method('getLabel')->willReturn($ltiProviderLabel);

        $container = new LtiDeliveryContainer();

        /** @var Extension|MockObject $extensionManager */
        $deliveryExtension = $this->getMockBuilder(Extension::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConfig'])
            ->getMock();
        $deliveryExtension->method('getConfig')->with('deliveryContainerRegistry')->willReturn(['lti' => $container]);

        /** @var ExtensionsManager|MockObject $extensionManager */
        $extensionManager = $this->getMockBuilder(ExtensionsManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionById'])
            ->getMock();
        $extensionManager->method('getExtensionById')->with('taoDelivery')->willReturn($deliveryExtension);

        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();
        $logger->expects($this->once())->method('info')->with(sprintf(
            'Creating LTI delivery with LTI provider "%s" ' . 'with LTI test url "%s" under delivery class "%s"',
            $ltiProviderLabel,
            $ltiPath,
            $classLabel
        ));

        /** @var EventManager|MockObject $eventManager */
        $eventManager = $this->getMockBuilder(EventManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['trigger'])
            ->getMock();
        $eventManager->expects($this->once())->method('trigger')->with($this->callback(
            function (DeliveryCreatedEvent $event) {
                return $event->getDeliveryUri() === self::RESOURCE_URI;
            }
        ));

        /** @var ServiceLocatorInterface|MockObject $serviceLocator */
        $serviceLocator = $this->getMockBuilder(ServiceLocatorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        $serviceLocator->method('get')->willReturnCallback(
            function ($id) use ($extensionManager, $logger, $eventManager) {
                switch ($id) {
                    case ExtensionsManager::SERVICE_ID:
                        return $extensionManager;
                    case LoggerService::SERVICE_ID:
                        return $logger;
                    case EventManager::SERVICE_ID:
                        return $eventManager;
                }
            }
        );

        $subject = new LtiDeliveryFactory();
        $subject->setServiceLocator($serviceLocator);

        $properties = [
            OntologyRdfs::RDFS_LABEL => $finalLabel,
            DeliveryAssemblyService::PROPERTY_DELIVERY_TIME => self::FIXED_TIME,
            ContainerRuntime::PROPERTY_CONTAINER => json_encode([
                'container' => 'lti',
                'params' => [
                    'ltiProvider' => $id,
                    'ltiPath' => $ltiPath,
                ],
            ]),
        ];

        if ($deliveryResource instanceof RdfResource) {
            $deliveryResource->expects($this->once())->method('setPropertiesValues')->with($properties);
            $expectedResource = $deliveryResource;
        } else {
            /** @var RdfResource|MockObject $anotherDeliveryResource */
            $expectedResource = $this->getMockBuilder(RdfResource::class)
                ->disableOriginalConstructor()
                ->setMethods(['getUri'])
                ->getMock();
            $expectedResource->method('getUri')->willReturn(self::RESOURCE_URI);
            $deliveryClass->method('createInstanceWithProperties')->with($properties)->willReturn($expectedResource);
        }

        $actual = $subject->create($deliveryClass, $ltiProvider, $ltiPath, $initialLabel, $deliveryResource);
        $this->assertInstanceOf(Report::class, $actual);
        $this->assertEquals(Report::TYPE_SUCCESS, $actual->getType());
        $this->assertEquals(__('LTI delivery successfully created.'), $actual->getMessage());
        $this->assertEquals($expectedResource, $actual->getData());
    }

    public function testGetLtiDeliveryContainerWithDeliveryAndNoLabel()
    {
        /** @var RdfResource|MockObject $deliveryResource */
        $deliveryResource = $this->getMockBuilder(RdfResource::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPropertiesValues', 'getUri'])
            ->getMock();
        $deliveryResource->method('getUri')->willReturn(self::RESOURCE_URI);

        $this->launchGetLtiDeliveryContainerTest('', 'LTI delivery 1', $deliveryResource);
    }

    public function testGetLtiDeliveryContainerWithDeliveryAndLabel()
    {
        /** @var RdfResource|MockObject $deliveryResource */
        $deliveryResource = $this->getMockBuilder(RdfResource::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPropertiesValues', 'getUri'])
            ->getMock();
        $deliveryResource->method('getUri')->willReturn(self::RESOURCE_URI);

        $this->launchGetLtiDeliveryContainerTest('deliveryLabel', 'deliveryLabel', $deliveryResource);
    }

    public function testGetLtiDeliveryContainerWithNoDeliveryAndNoLabel()
    {
        $this->launchGetLtiDeliveryContainerTest('', 'LTI delivery 1');
    }

    public function testGetLtiDeliveryContainerWithNoDeliveryAndLabel()
    {
        $this->launchGetLtiDeliveryContainerTest('deliveryLabel', 'deliveryLabel');
    }

    public function testDeferredCreate()
    {
        $label = 'task label';
        $deliveryClassUri = 'uri of delivery class';
        $ltiPath = 'some path';
        $ltiProviderLabel = 'label of the lti provider';
        $ltiProviderId = 'providerId';
        $resourceUri = 'resource URI';

        /** @var RdfClass|MockObject $deliveryClass */
        $deliveryClass = $this->getMockBuilder(RdfClass::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUri'])
            ->getMock();
        $deliveryClass->method('getUri')->willReturn($deliveryClassUri);

        /** @var LtiProvider|MockObject $ltiProvider */
        $ltiProvider = $this->getMockBuilder(LtiProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getLabel'])
            ->getMock();
        $ltiProvider->method('getLabel')->willReturn($ltiProviderLabel);
        $ltiProvider->method('getId')->willReturn($ltiProviderId);

        /** @var RdfResource|MockObject $deliveryResource */
        $deliveryResource = $this->getMockBuilder(RdfResource::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUri'])
            ->getMock();
        $deliveryResource->method('getUri')->willReturn($resourceUri);

        $params = [
            'deliveryClass' => $deliveryClassUri,
            'ltiProvider' => $ltiProviderId,
            'ltiPath' => $ltiPath,
            'label' => $label,
            'deliveryResource' => $resourceUri,
        ];

        /** @var QueueDispatcher|MockObject $queueDispatcher */
        $queueDispatcher = $this->getMockBuilder(QueueDispatcher::class)
            ->disableOriginalConstructor()
            ->setMethods(['createTask'])
            ->getMock();
        $queueDispatcher->method('createTask')->with(
            $this->callback(
            function ($callable) {
                return $callable instanceof LtiDeliveryCreationTask;
            }
        ),
            $params,
            __('Publishing of LTI delivery : "%s"', $ltiProviderLabel),
            null,
            true
        )->willReturn(true);

        /** @var ServiceLocatorInterface|MockObject $serviceLocator */
        $serviceLocator = $this->getMockBuilder(ServiceLocatorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        $serviceLocator->method('get')->willReturnCallback(
            function ($id) use ($queueDispatcher) {
                switch ($id) {
                    case QueueDispatcher::SERVICE_ID:
                        return $queueDispatcher;
                }
            }
        );

        $subject = new LtiDeliveryFactory();
        $subject->setServiceLocator($serviceLocator);

        $this->assertTrue($subject->deferredCreate($deliveryClass, $ltiProvider, $ltiPath, $label, $deliveryResource));
    }

    /**
     * Mocks general scope's time function.
     *
     * @param string $namespace
     * @param string $functionName
     * @param mixed  $value
     *
     * @return Mock
     * @throws \phpmock\MockEnabledException
     */
    protected function MockFunction($namespace, $functionName, $value)
    {
        $builder = new MockBuilder();
        $builder->setNamespace($namespace)
            ->setName($functionName)
            ->setFunction(
                function () use ($value) {
                    return $value;
                }
            );

        $mock = $builder->build();
        $mock->enable();

        return $mock;
    }
}

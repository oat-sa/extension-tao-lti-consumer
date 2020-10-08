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
 *
 */

namespace oat\taoLtiConsumer\test\unit\model\delivery\task;

use common_exception_MissingParameter as MissingParameterException;
use common_exception_InconsistentData as InconsistentDataException;
use common_report_Report as Report;
use core_kernel_classes_Class as RdfClass;
use core_kernel_classes_Resource as RdfResource;
use core_kernel_persistence_smoothsql_SmoothModel;
use oat\generis\test\TestCase;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;
use oat\taoLtiConsumer\model\delivery\factory\LtiDeliveryFactory;
use oat\taoLtiConsumer\model\delivery\task\LtiDeliveryCreationTask;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Zend\ServiceManager\ServiceLocatorInterface;

class LtiDeliveryCreationTaskTest extends TestCase
{
    /**
     * @dataProvider paramsToTest
     *
     * @param string $deliveryClassName
     * @param bool   $existingClass
     * @param string $label
     * @param string $deliveryResourceId
     *
     * @throws MissingParameterException
     * @throws InconsistentDataException
     */
    public function testInvoke($deliveryClassName, $existingClass, $label, $deliveryResourceId)
    {
        $ltiProviderId = 'ltiProviderId';
        $ltiPath = 'ltiPath';
        $params = [
            'ltiProvider' => $ltiProviderId,
            'ltiPath' => $ltiPath,
        ];
        if ($deliveryClassName !== '' && $existingClass) {
            $params['deliveryClass'] = $deliveryClassName;
        } else {
            $deliveryClassName = DeliveryAssemblyService::CLASS_URI;
        }
        /** @var RdfClass|MockObject $deliveryClass */
        $deliveryClass = $this->getMockBuilder(RdfClass::class)
            ->disableOriginalConstructor()
            ->setMethods(['exists'])
            ->getMock();
        $deliveryClass->method('exists')->willReturn($existingClass);

        if ($label !== '') {
            $params['label'] = $label;
        }
        if ($deliveryResourceId !== null) {
            $params['deliveryResource'] = $deliveryResourceId;
        }

        /** @var Report|MockObject $report */
        $report = $this->getMockBuilder(Report::class)
            ->disableOriginalConstructor()
            ->setMethods(['getType'])
            ->getMock();
        $report->method('getType')->willReturn($deliveryResourceId !== null ? Report::TYPE_ERROR : Report::TYPE_SUCCESS);

        /** @var LtiProvider|MockObject $providerResource */
        $ltiProvider = $this->getMockBuilder(LtiProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getLabel'])
            ->getMock();

        /** @var RdfResource|MockObject $deliveryResource */
        $deliveryResource = $this->getMockBuilder(RdfResource::class)
            ->disableOriginalConstructor()
            ->setMethods(['delete'])
            ->getMock();

        /** @var core_kernel_persistence_smoothsql_SmoothModel|MockObject $providerModel */
        $providerModel = $this->getMockBuilder(core_kernel_persistence_smoothsql_SmoothModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResource', 'getClass'])
            ->getMockForAbstractClass();
        $providerModel->method('getResource')->willReturnCallback(
            function ($id) use ($deliveryResourceId, $deliveryResource) {
                if ($id === $deliveryResourceId) {
                    return $deliveryResource;
                }
            }
        );
        $providerModel->method('getClass')->with($deliveryClassName)->willReturn($deliveryClass);

        /** @var LtiDeliveryFactory|MockObject $litDeliveryFactory */
        $litDeliveryFactory = $this->getMockBuilder(LtiDeliveryFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $litDeliveryFactory->method('create')
            ->with($deliveryClass, $ltiProvider, $ltiPath, $label, $deliveryResourceId !== null ? $deliveryResource : null)
            ->willReturn($report);

        /** @var LtiProviderService|MockObject $litDeliveryFactory */
        $ltiProviderService = $this->getMockBuilder(LtiProviderService::class)
            ->disableOriginalConstructor()
            ->setMethods(['searchById', 'searchByLabel'])
            ->getMock();

        $ltiProviderService->method('searchByLabel')->with($ltiProviderId)->willReturn([$ltiProvider]);
        $ltiProviderService->method('searchById')->with($ltiProviderId)->willReturn($ltiProvider);

        /** @var ServiceLocatorInterface|MockObject $serviceLocator */
        $serviceLocator = $this->getMockBuilder(ServiceLocatorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $serviceLocator->method('get')->willReturnCallback(
            function ($id) use ($litDeliveryFactory, $ltiProviderService) {
                switch ($id) {
                    case LtiDeliveryFactory::class:
                        return $litDeliveryFactory;
                    case LtiProviderService::class:
                        return $ltiProviderService;
                }
            }
        );

        $subject = new LtiDeliveryCreationTask();
        $subject->setModel($providerModel);
        $subject->setServiceLocator($serviceLocator);

        $this->assertEquals($report, $subject($params));
    }

    public function paramsToTest()
    {
        return [
            ['', false, '', null],
            ['', false, '', 'deliveryResource'],
            ['', false, 'the label', 'deliveryResource'],
            ['delivery class', false, 'the label', 'deliveryResource'],
            ['delivery class', true, 'the label', 'deliveryResource'],
        ];
    }

    /**
     * @dataProvider parametersToTest
     *
     * @param array  $params
     * @param string $missing
     */
    public function testInvokeWithMissingParametersThrowsException($params, $missing)
    {
        $this->expectException(MissingParameterException::class);
        $this->expectExceptionMessage('Expected parameter ' . $missing . ' passed to ' . LtiDeliveryCreationTask::class);
        $subject = new LtiDeliveryCreationTask();
        $subject($params);
    }

    public function parametersToTest()
    {
        return [
            [['ltiProvider' => 0], 'ltiPath'],
            [['ltiPath' => 0], 'ltiProvider'],
        ];
    }

    /**
     * @return string
     */
    public function testJsonSerialize()
    {
        $subject = new LtiDeliveryCreationTask();
        $this->assertEquals(LtiDeliveryCreationTask::class, $subject->jsonSerialize());
    }
}

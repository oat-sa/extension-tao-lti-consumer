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
 *
 */

namespace oat\taoLtiConsumer\test\unit\model\delivery\container;

use core_kernel_classes_Resource as RdfResource;
use IMSGlobal\LTI\ToolProvider\ToolConsumer;
use oat\generis\test\unit\OntologyMockTest;
use oat\oatbox\session\SessionService;
use oat\oatbox\user\User;
use oat\tao\helpers\UrlHelper;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;
use oat\taoLtiConsumer\model\delivery\container\LtiDeliveryContainer;
use oat\taoLtiConsumer\model\delivery\container\LtiExecutionContainer;
use phpmock\MockBuilder;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Zend\ServiceManager\ServiceLocatorInterface;

class LtiDeliveryContainerTest extends OntologyMockTest
{
    public function testGetExecutionContainer()
    {
        $identifier = 'delivery identifier';
        $ltiProviderId = 'lti provider id';
        $ltiUrl = 'path to lti';
        $consumerKey = 'consumerKey';
        $consumerSecret = 'consumerSecret';
        $returnUrl = 'returnUrl';
        $userId = 'userId';
        $md5 = 'random-md5';

        $params = [
            'ltiProvider' => $ltiProviderId,
            'ltiPath' => $ltiUrl,
        ];

        /** @var RdfResource|MockObject $delivery */
        $delivery = $this->getMockBuilder(RdfResource::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUri'])
            ->getMock();

        /** @var DeliveryExecution|MockObject $execution */
        $execution = $this->getMockBuilder(DeliveryExecution::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDelivery', 'getIdentifier'])
            ->getMock();
        $execution->method('getDelivery')->willReturn($delivery);
        $execution->method('getIdentifier')->willReturn($identifier);

        /** @var User|MockObject $serviceLocator */
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIdentifier'])
            ->getMockForAbstractClass();
        $user->method('getIdentifier')->willReturn($userId);

        /** @var SessionService|MockObject $serviceLocator */
        $sessionService = $this->getMockBuilder(SessionService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentUser'])
            ->getMock();
        $sessionService->method('getCurrentUser')->willReturn($user);

        /** @var LtiProvider|MockObject $ltiProvider */
        $ltiProvider = $this->getMockBuilder(LtiProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSecret', 'getKey'])
            ->getMock();
        $ltiProvider->method('getKey')->willReturn($consumerKey);
        $ltiProvider->method('getSecret')->willReturn($consumerSecret);

        /** @var LtiProviderService|MockObject $serviceLocator */
        $ltiProviderService = $this->getMockBuilder(LtiProviderService::class)
            ->disableOriginalConstructor()
            ->setMethods(['searchById'])
            ->getMock();
        $ltiProviderService->method('searchById')->willReturn($ltiProvider);

        $urlHelper = $this->getMockBuilder(UrlHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['buildUrl'])
            ->getMock();
        $urlHelper->method('buildUrl')->willReturnCallback(
            function ($method, $class, $extension) {
                return $extension . '/' . $class . '/' . $method;
            }
        );

        /** @var ServiceLocatorInterface|MockObject $serviceLocator */
        $serviceLocator = $this->getMockBuilder(ServiceLocatorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $serviceLocator->method('get')->willReturnCallback(
            function ($id) use ($sessionService, $ltiProviderService, $urlHelper) {
                switch ($id) {
                    case SessionService::SERVICE_ID:
                        return $sessionService;
                    case LtiProviderService::class:
                        return $ltiProviderService;
                    case UrlHelper::class:
                        return $urlHelper;
                }
            }
        );
        $data = [
            LtiLaunchData::LTI_MESSAGE_TYPE => 'basic-lti-launch-request',
            LtiLaunchData::LTI_VERSION => 'LTI-1p0',
            LtiLaunchData::RESOURCE_LINK_ID => $identifier,
            LtiLaunchData::USER_ID => $userId,
            LtiLaunchData::ROLES => 'Learner',
            LtiLaunchData::LAUNCH_PRESENTATION_RETURN_URL => 'taoDelivery/DeliveryServer/index',
            LtiLaunchData::LIS_RESULT_SOURCEDID => $identifier,
            LtiLaunchData::LIS_OUTCOME_SERVICE_URL => 'taoLtiConsumer/ResultController/manageResults',
        ];

        // Mock general scope's md5 function to have a testable signature.
        $mockedMd5 = $this->mockGlobalFunction('IMSGlobal\LTI\OAuth', 'md5', $md5);
        $data = ToolConsumer::addSignature($ltiUrl, $consumerKey, $consumerSecret, $data);

        $subject = new LtiDeliveryContainer();

        $subject->setRuntimeParams($params);
        $subject->setServiceLocator($serviceLocator);

        $expected = new LtiExecutionContainer($execution);
        $expected->setData('launchUrl', $ltiUrl);
        $expected->setData('launchParams', $data);

        $this->assertEquals($expected, $subject->getExecutionContainer($execution));
        $mockedMd5->disable();
    }

    public function mockGlobalFunction($namespace, $name, $value)
    {
        // Mock general scope's md5 function to have a testable signature.
        $builder = new MockBuilder();
        $builder->setNamespace($namespace)
            ->setName($name)
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

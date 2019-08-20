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
use oat\generis\model\data\Model;
use oat\generis\test\unit\OntologyMockTest;
use oat\oatbox\session\SessionService;
use oat\oatbox\user\User;
use oat\tao\model\oauth\DataStore;
use oat\taoDelivery\model\execution\DeliveryExecution;
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
        $ltiProvider = [
            DataStore::PROPERTY_OAUTH_KEY => [$consumerKey],
            DataStore::PROPERTY_OAUTH_SECRET => [$consumerSecret],
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

        /** @var RdfResource|MockObject $providerResource */
        $providerResource = $this->getMockBuilder(RdfResource::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPropertiesValues'])
            ->getMock();
        $providerResource->method('getPropertiesValues')->with([
            DataStore::PROPERTY_OAUTH_KEY,
            DataStore::PROPERTY_OAUTH_SECRET,
        ])->willReturn($ltiProvider);

        /** @var Model|MockObject $providerModel */
        $providerModel = $this->getMockBuilder(Model::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResource'])
            ->getMockForAbstractClass();
        $providerModel->method('getResource')->with($ltiProviderId)->willReturn($providerResource);

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

        /** @var ServiceLocatorInterface|MockObject $serviceLocator */
        $serviceLocator = $this->getMockBuilder(ServiceLocatorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        $serviceLocator->method('get')->with(SessionService::SERVICE_ID)->willReturn($sessionService);

        $data = [
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',
            'resource_link_id' => $identifier,
            'user_id' => $userId,
            'roles' => 'Learner',
            'launch_presentation_return_url' => $returnUrl,
            'lis_result_sourcedid' => $identifier,
        ];

        // Mock general scope's md5 function to have a testable signature.
        $mockedMd5 = $this->mockGlobalFunction('IMSGlobal\LTI\OAuth', 'md5', $md5);
        $data = ToolConsumer::addSignature($ltiUrl, $consumerKey, $consumerSecret, $data);

        $mockedUrl = $this->mockGlobalFunction('oat\taoLtiConsumer\model\delivery\container', '_url', $returnUrl);

        $subject = new LtiDeliveryContainer();

        $subject->setRuntimeParams($params);
        $subject->setModel($providerModel);
        $subject->setServiceLocator($serviceLocator);

        $expected = new LtiExecutionContainer($execution);
        $expected->setData('launchUrl', $ltiUrl);
        $expected->setData('launchParams', $data);

        $this->assertEquals($expected, $subject->getExecutionContainer($execution));
        $mockedMd5->disable();
        $mockedUrl->disable();
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

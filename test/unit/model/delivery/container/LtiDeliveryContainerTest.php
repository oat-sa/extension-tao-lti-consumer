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

use oat\generis\test\TestCase;
use oat\oatbox\log\LoggerService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;
use oat\taoLti\models\classes\Tool\LtiLaunch;
use oat\taoLti\models\classes\Tool\LtiLaunchCommandInterface;
use oat\taoLti\models\classes\Tool\Service\LtiLauncherProxy;
use oat\taoLtiConsumer\model\delivery\container\LtiDeliveryContainer;
use oat\taoLtiConsumer\model\delivery\container\LtiExecutionContainer;
use oat\taoLtiConsumer\model\Tool\Factory\LtiDeliveryLaunchCommandFactoryProxy;
use PHPUnit\Framework\MockObject\MockObject;

class LtiDeliveryContainerTest extends TestCase
{
    /** @var LtiDeliveryContainer */
    private $subject;

    /** @var LtiLauncherProxy|MockObject */
    private $launcherProxy;

    /** @var LtiDeliveryLaunchCommandFactoryProxy|MockObject */
    private $launchCommandFactoryProxy;

    /** @var LtiProviderService|MockObject */
    private $ltiProvider;

    /** @var LoggerService|MockObject */
    private $logger;

    public function setUp(): void
    {
        $this->logger = $this->createMock(LoggerService::class);
        $this->ltiProvider = $this->createMock(LtiProviderService::class);
        $this->launchCommandFactoryProxy = $this->createMock(LtiDeliveryLaunchCommandFactoryProxy::class);
        $this->launcherProxy = $this->createMock(LtiLauncherProxy::class);
        $this->subject = new LtiDeliveryContainer();
        $this->subject->setLogger($this->logger);
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    LtiProviderService::class => $this->ltiProvider,
                    LtiDeliveryLaunchCommandFactoryProxy::class => $this->launchCommandFactoryProxy,
                    LtiLauncherProxy::class => $this->launcherProxy,
                    LoggerService::SERVICE_ID => $this->logger,
                ]
            )
        );
        $this->subject->setRuntimeParams(
            [
                LtiDeliveryContainer::CONTAINER_LTI_LAUNCH_URL => 'launchUrl',
                LtiDeliveryContainer::CONTAINER_LTI_PROVIDER_ID => 'providerId',
            ]
        );
    }

    public function testGetExecutionContainer(): void
    {
        $provider = $this->createMock(LtiProvider::class);
        $command = $this->createMock(LtiLaunchCommandInterface::class);
        $launch = new LtiLaunch('launchUrl', ['param' => 'value']);

        $this->ltiProvider
            ->method('searchById')
            ->willReturn($provider);

        $this->launchCommandFactoryProxy
            ->method('create')
            ->willReturn($command);

        $this->launcherProxy
            ->method('launch')
            ->willReturn($launch);

        $this->logger
            ->method('debug');

        $execution = $this->createMock(DeliveryExecution::class);

        $container = new LtiExecutionContainer($execution);
        $container->setData('launchUrl', 'launchUrl');
        $container->setData('launchParams', ['param' => 'value']);

        $this->assertEquals(
            $container,
            $this->subject->getExecutionContainer($execution)
        );
    }
}

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

namespace oat\taoLtiConsumer\test\unit\model\Tool\Factory;

use LogicException;
use oat\generis\test\TestCase;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\Tool\LtiLaunchCommandInterface;
use oat\taoLtiConsumer\model\Tool\Factory\Lti1p1DeliveryLaunchCommandFactory;
use oat\taoLtiConsumer\model\Tool\Factory\Lti1p3DeliveryLaunchCommandFactory;
use oat\taoLtiConsumer\model\Tool\Factory\LtiDeliveryLaunchCommandFactoryProxy;
use PHPUnit\Framework\MockObject\MockObject;

class LtiDeliveryLaunchCommandFactoryProxyTest extends TestCase
{
    /** @var Lti1p1DeliveryLaunchCommandFactory|MockObject */
    private $lti1p1DeliveryLaunchCommandFactory;

    /** @var Lti1p3DeliveryLaunchCommandFactory|MockObject */
    private $lti1p3DeliveryLaunchCommandFactory;

    /** @var LtiDeliveryLaunchCommandFactoryProxy */
    private $subject;

    public function setUp(): void
    {
        $this->lti1p1DeliveryLaunchCommandFactory = $this->createMock(Lti1p1DeliveryLaunchCommandFactory::class);
        $this->lti1p3DeliveryLaunchCommandFactory = $this->createMock(Lti1p3DeliveryLaunchCommandFactory::class);
        $this->subject = new LtiDeliveryLaunchCommandFactoryProxy();
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    Lti1p1DeliveryLaunchCommandFactory::class => $this->lti1p1DeliveryLaunchCommandFactory,
                    Lti1p3DeliveryLaunchCommandFactory::class => $this->lti1p3DeliveryLaunchCommandFactory
                ]
            )
        );
    }

    public function testCreateLti1p1Command(): void
    {
        $expectedCommand = $this->createMock(LtiLaunchCommandInterface::class);

        $ltiProvider = $this->createMock(LtiProvider::class);

        $ltiProvider->method('getLtiVersion')
            ->willReturn('1.1');

        $this->lti1p1DeliveryLaunchCommandFactory
            ->method('create')
            ->willReturn($expectedCommand);

        $this->assertEquals(
            $expectedCommand,
            $this->subject->create(
                [
                    'ltiProvider' => $ltiProvider,
                ]
            )
        );
    }

    public function testCreateLti1p3Command(): void
    {
        $expectedCommand = $this->createMock(LtiLaunchCommandInterface::class);

        $ltiProvider = $this->createMock(LtiProvider::class);

        $ltiProvider->method('getLtiVersion')
            ->willReturn('1.3');

        $this->lti1p3DeliveryLaunchCommandFactory
            ->method('create')
            ->willReturn($expectedCommand);

        $this->assertEquals(
            $expectedCommand,
            $this->subject->create(
                [
                    'ltiProvider' => $ltiProvider,
                ]
            )
        );
    }

    public function testCreateUnsupportedLtiVersionWillThrowException(): void
    {
        $ltiProvider = $this->createMock(LtiProvider::class);

        $ltiProvider->method('getLtiVersion')
            ->willReturn('1.4');

        $this->expectExceptionMessage(LogicException::class);
        $this->expectExceptionMessage('LTI version 1.4 is not supported');

        $this->subject->create(
            [
                'ltiProvider' => $ltiProvider,
            ]
        );
    }
}

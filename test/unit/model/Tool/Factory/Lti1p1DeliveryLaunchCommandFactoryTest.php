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

use oat\generis\test\TestCase;
use oat\oatbox\session\SessionService;
use oat\oatbox\user\User;
use oat\tao\helpers\UrlHelper;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\Tool\LtiLaunchCommand;
use oat\taoLtiConsumer\model\Tool\Factory\Lti1p1DeliveryLaunchCommandFactory;
use PHPUnit\Framework\MockObject\MockObject;

class Lti1p1DeliveryLaunchCommandFactoryTest extends TestCase
{
    /** @var SessionService|MockObject */
    private $sessionService;

    /** @var UrlHelper|MockObject */
    private $urlHelper;

    /** @var Lti1p1DeliveryLaunchCommandFactory */
    private $subject;

    public function setUp(): void
    {
        $this->sessionService = $this->createMock(SessionService::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);
        $this->subject = new Lti1p1DeliveryLaunchCommandFactory();
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    SessionService::SERVICE_ID => $this->sessionService,
                    UrlHelper::class => $this->urlHelper,
                ]
            )
        );
    }

    public function testCreate(): void
    {
        $execution = $this->expectExecution();
        $ltiProvider = $this->createMock(LtiProvider::class);
        $user = $this->expectUser();

        $this->urlHelper
            ->method('buildUrl')
            ->willReturnOnConsecutiveCalls(
                'returnUrl',
                'outcomeServiceUrl'
            );

        $expectedCommand = new LtiLaunchCommand(
            $ltiProvider,
            [
                'Learner'
            ],
            [
                LtiLaunchData::LTI_MESSAGE_TYPE => 'basic-lti-launch-request',
                LtiLaunchData::RESOURCE_LINK_ID => 'deliveryExecutionIdentifier',
                LtiLaunchData::LAUNCH_PRESENTATION_RETURN_URL => 'returnUrl',
                LtiLaunchData::LIS_RESULT_SOURCEDID => 'deliveryExecutionIdentifier',
                LtiLaunchData::LIS_OUTCOME_SERVICE_URL => 'outcomeServiceUrl',
            ],
            'deliveryExecutionIdentifier',
            $user,
            null,
            'launchUrl'
        );

        $config = [
            'launchUrl' => 'launchUrl',
            'ltiProvider' => $ltiProvider,
            'deliveryExecution' => $execution
        ];

        $this->assertEquals($expectedCommand, $this->subject->create($config));
    }

    private function expectUser(): User
    {
        $user = $this->createMock(User::class);

        $user->method('getIdentifier')
            ->willReturn('userIdentifier');

        $this->sessionService
            ->method('getCurrentUser')
            ->willReturn($user);

        return $user;
    }

    private function expectExecution(): DeliveryExecution
    {
        $execution = $this->createMock(DeliveryExecution::class);

        $execution->method('getIdentifier')
            ->willReturn('deliveryExecutionIdentifier');

        return $execution;
    }
}

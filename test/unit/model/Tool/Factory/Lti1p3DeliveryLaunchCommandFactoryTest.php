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
use OAT\Library\Lti1p3Core\Message\Payload\Claim\BasicOutcomeClaim;
use oat\oatbox\session\SessionService;
use oat\oatbox\user\User;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\Tool\LtiLaunchCommand;
use oat\taoLtiConsumer\model\Tool\Factory\LisOutcomeServiceUrlFactory;
use oat\taoLtiConsumer\model\Tool\Factory\Lti1p3DeliveryLaunchCommandFactory;
use oat\taoLtiConsumer\model\Tool\Service\ResourceLinkIdDiscover;
use oat\taoLtiConsumer\model\Tool\Service\ResourceLinkIdDiscoverInterface;
use PHPUnit\Framework\MockObject\MockObject;

class Lti1p3DeliveryLaunchCommandFactoryTest extends TestCase
{
    /** @var ResourceLinkIdDiscoverInterface|MockObject */
    private $resourceLinkIdDiscover;

    /** @var SessionService|MockObject */
    private $sessionService;

    /** @var LisOutcomeServiceUrlFactory|MockObject */
    private $lisOutcomeServiceUrlFactory;

    /** @var Lti1p3DeliveryLaunchCommandFactory */
    private $subject;

    public function setUp(): void
    {
        $this->sessionService = $this->createMock(SessionService::class);
        $this->lisOutcomeServiceUrlFactory = $this->createMock(LisOutcomeServiceUrlFactory::class);
        $this->resourceLinkIdDiscover = $this->createMock(ResourceLinkIdDiscoverInterface::class);
        $this->subject = new Lti1p3DeliveryLaunchCommandFactory();
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    SessionService::SERVICE_ID => $this->sessionService,
                    ResourceLinkIdDiscover::class => $this->resourceLinkIdDiscover,
                    LisOutcomeServiceUrlFactory::class => $this->lisOutcomeServiceUrlFactory,
                ]
            )
        );
    }

    public function testCreate(): void
    {
        $execution = $this->createMock(DeliveryExecution::class);

        $execution->method('getOriginalIdentifier')
            ->willReturn('deliveryExecutionIdentifier');

        $ltiProvider = $this->createMock(LtiProvider::class);

        $user = $this->createMock(User::class);

        $user->method('getIdentifier')
            ->willReturn('userIdentifier');

        $this->sessionService
            ->method('getCurrentUser')
            ->willReturn($user);

        $this->lisOutcomeServiceUrlFactory
            ->method('create')
            ->willReturn('outcomeServiceUrl');

        $config = [
            'launchUrl' => 'launchUrl',
            'ltiProvider' => $ltiProvider,
            'deliveryExecution' => $execution
        ];

        $this->resourceLinkIdDiscover
            ->method('discoverByDeliveryExecution')
            ->with($execution, $config)
            ->willReturn('deliveryExecutionIdentifier');

        $expectedCommand = new LtiLaunchCommand(
            $ltiProvider,
            [
                'Learner'
            ],
            [
                new BasicOutcomeClaim(
                    'deliveryExecutionIdentifier',
                    'outcomeServiceUrl'
                ),
                'https://purl.imsglobal.org/spec/lti/claim/launch_presentation' => ['return_url' => _url('index', 'DeliveryServer', 'taoDelivery')]
            ],
            'deliveryExecutionIdentifier',
            $user,
            'userIdentifier',
            'launchUrl'
        );

        $this->assertEquals($expectedCommand, $this->subject->create($config));
    }
}

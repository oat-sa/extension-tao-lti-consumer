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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA
 */

declare(strict_types=1);

namespace oat\taoLtiConsumer\model\Tool\Factory;

use oat\generis\model\OntologyAwareTrait;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\BasicOutcomeClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\session\SessionService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\models\classes\ReturnUrlService;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\Tool\Factory\LtiLaunchCommandFactoryInterface;
use oat\taoLti\models\classes\Tool\LtiLaunchCommand;
use oat\taoLti\models\classes\Tool\LtiLaunchCommandInterface;
use oat\taoLtiConsumer\model\Tool\Service\ResourceLinkIdDiscover;
use oat\taoLtiConsumer\model\Tool\Service\ResourceLinkIdDiscoverInterface;

class Lti1p3DeliveryLaunchCommandFactory extends ConfigurableService implements LtiLaunchCommandFactoryInterface
{
    use OntologyAwareTrait;

    public function create(array $config): LtiLaunchCommandInterface
    {
        $launchUrl = $config['launchUrl'];

        /** @var LtiProvider $ltiProvider */
        $ltiProvider = $config['ltiProvider'];

        /** @var DeliveryExecution $execution */
        $execution = $config['deliveryExecution'];

        $resourceIdentifier = $this->getResourceLinkIdDiscover()
            ->discoverByDeliveryExecution($execution, $config);

        $user = $this->getSessionService()
            ->getCurrentUser();

        return new LtiLaunchCommand(
            $ltiProvider,
            [
                'Learner'
            ],
            [
                new BasicOutcomeClaim(
                    $execution->getOriginalIdentifier(),
                    $this->getLisOutcomeServiceUrlFactory()->create()
                ),
                LtiMessagePayloadInterface::CLAIM_LTI_LAUNCH_PRESENTATION => ['return_url' => $this->getReturnUrl()],
            ],
            $resourceIdentifier,
            $user,
            $user->getIdentifier(),
            $launchUrl
        );
    }

    private function getSessionService(): SessionService
    {
        return $this->getServiceLocator()->get(SessionService::SERVICE_ID);
    }

    private function getResourceLinkIdDiscover(): ResourceLinkIdDiscoverInterface
    {
        return $this->getServiceLocator()->get(ResourceLinkIdDiscover::class);
    }

    private function getLisOutcomeServiceUrlFactory(): LisOutcomeServiceUrlFactory
    {
        return $this->getServiceLocator()->get(LisOutcomeServiceUrlFactory::class);
    }

    private function getReturnUrl()
    {
        if ($this->getServiceLocator()->has(ReturnUrlService::SERVICE_ID)) {
            return $this->getServiceLocator()->get(ReturnUrlService::SERVICE_ID)->getReturnUrl();
        }
        return _url('index', 'DeliveryServer', 'taoDelivery');
    }

}

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

namespace oat\taoLtiConsumer\model\delivery\container;

use oat\generis\model\OntologyAwareTrait;
use OAT\Library\Lti1p3Core\Message\Claim\ContextClaim;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\session\SessionService;
use oat\oatbox\user\User;
use oat\tao\helpers\UrlHelper;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\platform\builder\Lti1p1LaunchBuilder;
use oat\taoLti\models\platform\builder\Lti1p3LaunchBuilder;
use oat\taoLti\models\platform\builder\LtiLaunchBuilderInterface;
use oat\taoLti\models\tool\launch\LtiLaunchInterface;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;

class LtiDeliveryLaunchProxy extends ConfigurableService
{
    use OntologyAwareTrait;

    public function launch(
        string $launchUrl,
        LtiProvider $ltiProvider,
        DeliveryExecution $execution
    ): LtiLaunchInterface
    {
        /** @var User $user */
        $user = $this->getServiceLocator()
            ->get(SessionService::SERVICE_ID)
            ->getCurrentUser();

        if ($ltiProvider->getLtiVersion() === '1.3') {
            /*
             * @TODO Missing add return UR / or callback URL...
             * @TODO Add necessary claims
             * @TODO Add necessary roles
             */
            return $this->getLti1p3Builder()
                ->withProvider($ltiProvider)
                ->withUser($user)
                ->withClaims(
                    [
                        new ContextClaim('contextId'),
                        'myCustomClaim' => 'myCustomValue'
                    ]
                )->withRoles(
                    [
                        'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner'
                    ]
                )->build();
        }

        if ($ltiProvider->getLtiVersion() === '1.1') {
            $urlHelper = $this->getUrlHelper();
            $returnUrl = $urlHelper->buildUrl('index', 'DeliveryServer', 'taoDelivery');
            $outcomeServiceUrl = $urlHelper->buildUrl('manageResults', 'ResultController', 'taoLtiConsumer');

            return $this->getLti1p1Builder()
                ->withUser($this->getServiceLocator()->get(SessionService::SERVICE_ID)->getCurrentUser())
                ->withProvider($ltiProvider)
                ->withLaunchUrl($launchUrl)
                ->withRoles(
                    [
                        'Learner'
                    ]
                )
                ->withClaims(
                    [
                        LtiLaunchData::LTI_MESSAGE_TYPE => 'basic-lti-launch-request',
                        LtiLaunchData::RESOURCE_LINK_ID => $execution->getIdentifier(),
                        LtiLaunchData::LAUNCH_PRESENTATION_RETURN_URL => $returnUrl,
                        LtiLaunchData::LIS_RESULT_SOURCEDID => $execution->getIdentifier(),
                        LtiLaunchData::LIS_OUTCOME_SERVICE_URL => $outcomeServiceUrl,
                    ]
                )->build();
        }
    }

    private function getUrlHelper(): UrlHelper
    {
        return $this->getServiceLocator()->get(UrlHelper::class);
    }

    private function getLti1p1Builder(): LtiLaunchBuilderInterface
    {
        return $this->getServiceLocator()->get(Lti1p1LaunchBuilder::class);
    }

    private function getLti1p3Builder(): LtiLaunchBuilderInterface
    {
        return $this->getServiceLocator()->get(Lti1p3LaunchBuilder::class);
    }
}

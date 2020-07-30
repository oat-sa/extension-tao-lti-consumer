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
 */

namespace oat\taoLtiConsumer\model\delivery\factory;

use oat\oatbox\service\ConfigurableService;
use oat\oatbox\session\SessionService;
use oat\tao\helpers\UrlHelper;
use oat\taoLti\models\platform\builder\Lti1p1LaunchBuilder;
use oat\taoLti\models\platform\builder\LtiLaunchBuilderInterface;
use oat\taoLti\models\tool\launch\factory\LtiLaunchFactoryInterface;
use oat\taoLti\models\tool\launch\LtiLaunchInterface;
use oat\taoLti\models\tool\launch\LtiLaunchParams;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;

class Lti1p1LaunchFactory extends ConfigurableService implements LtiLaunchFactoryInterface
{
    public function create(LtiLaunchParams $params): LtiLaunchInterface
    {
        $urlHelper = $this->getUrlHelper();
        $returnUrl = $urlHelper->buildUrl('index', 'DeliveryServer', 'taoDelivery');
        $outcomeServiceUrl = $urlHelper->buildUrl('manageResults', 'ResultController', 'taoLtiConsumer');

        return $this->getBuilder()
            ->withUser($this->getServiceLocator()->get(SessionService::SERVICE_ID)->getCurrentUser())
            ->withProvider($this->getLtiProvider($params->getProviderId()))
            ->withLaunchUrl($params->getLaunchUrl())
            ->withRoles(
                [
                    'Learner'
                ]
            )
            ->withClaims(
                [
                    LtiLaunchData::LTI_MESSAGE_TYPE => 'basic-lti-launch-request',
                    LtiLaunchData::RESOURCE_LINK_ID => $params->getResourceLinkId(),
                    LtiLaunchData::LAUNCH_PRESENTATION_RETURN_URL => $returnUrl,
                    LtiLaunchData::LIS_RESULT_SOURCEDID => $params->getResourceLinkId(),
                    LtiLaunchData::LIS_OUTCOME_SERVICE_URL => $outcomeServiceUrl,
                ]
            )->build();
    }

    private function getUrlHelper(): UrlHelper
    {
        return $this->getServiceLocator()->get(UrlHelper::class);
    }

    private function getBuilder(): LtiLaunchBuilderInterface
    {
        return $this->getServiceLocator()->get(Lti1p1LaunchBuilder::class);
    }

    private function getLtiProvider($id): LtiProvider
    {
        return $this->getServiceLocator()->get(LtiProviderService::class)->searchById($id);
    }
}

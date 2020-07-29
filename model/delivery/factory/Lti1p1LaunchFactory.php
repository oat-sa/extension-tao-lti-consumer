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

use IMSGlobal\LTI\ToolProvider\ToolConsumer;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\session\SessionService;
use oat\tao\helpers\UrlHelper;
use oat\taoLti\models\tool\launch\factory\LtiLaunchFactoryInterface;
use oat\taoLti\models\tool\launch\LtiLaunchInterface;
use oat\taoLti\models\tool\launch\LtiLaunchParams;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;
use oat\taoLtiConsumer\model\delivery\container\Lti1p1DeliveryLaunch;

class Lti1p1LaunchFactory extends ConfigurableService implements LtiLaunchFactoryInterface
{
    public function create(LtiLaunchParams $params): LtiLaunchInterface
    {
        $ltiLaunchUrl = $params->getLaunchUrl();
        $ltiProvider = $this->getLtiProvider($params->getProviderId());
        $consumerKey = $ltiProvider->getKey();
        $consumerSecret = $ltiProvider->getSecret();
        $userId = $this->getServiceLocator()->get(SessionService::SERVICE_ID)->getCurrentUser()->getIdentifier();

        $urlHelper = $this->getUrlHelper();
        $returnUrl = $urlHelper->buildUrl('index', 'DeliveryServer', 'taoDelivery');
        $outcomeServiceUrl = $urlHelper->buildUrl('manageResults', 'ResultController', 'taoLtiConsumer');

        $data = [
            LtiLaunchData::LTI_MESSAGE_TYPE => 'basic-lti-launch-request',
            LtiLaunchData::LTI_VERSION => 'LTI-1p0',
            LtiLaunchData::RESOURCE_LINK_ID => $params->getResourceLinkId(),
            LtiLaunchData::USER_ID => $userId,
            LtiLaunchData::ROLES => 'Learner',
            LtiLaunchData::LAUNCH_PRESENTATION_RETURN_URL => $returnUrl,
            LtiLaunchData::LIS_RESULT_SOURCEDID => $params->getResourceLinkId(),
            LtiLaunchData::LIS_OUTCOME_SERVICE_URL => $outcomeServiceUrl,
        ];
        $data = ToolConsumer::addSignature($ltiLaunchUrl, $consumerKey, $consumerSecret, $data);

        return new Lti1p1DeliveryLaunch($ltiLaunchUrl, $data);
    }

    private function getUrlHelper(): UrlHelper
    {
        return $this->getServiceLocator()->get(UrlHelper::class);
    }

    private function getLtiProvider($id): LtiProvider
    {
        return $this->getServiceLocator()->get(LtiProviderService::class)->searchById($id);
    }
}

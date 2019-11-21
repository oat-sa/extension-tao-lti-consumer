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

namespace oat\taoLtiConsumer\model\delivery\container;

use IMSGlobal\LTI\ToolProvider\ToolConsumer;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\session\SessionService;
use oat\tao\helpers\UrlHelper;
use oat\taoDelivery\model\container\delivery\AbstractContainer;
use oat\taoDelivery\model\container\execution\ExecutionClientContainer;
use oat\taoDelivery\model\container\ExecutionContainer;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;
use oat\taoLtiConsumer\model\AnonymizeHelper;

/**
 * Class LtiDeliveryContainer
 *
 * A delivery container to manage LTI based delivery
 */
class LtiDeliveryContainer extends AbstractContainer
{
    use OntologyAwareTrait;

    /**
     * Get the execution container to render LTI based delivery
     *
     * @param DeliveryExecution $execution
     *
     * @return ExecutionClientContainer|ExecutionContainer
     */
    public function getExecutionContainer(DeliveryExecution $execution)
    {
        $params = $this->getRuntimeParams();
        $ltiUrl = $params['ltiPath'];

        $ltiProvider = $this->getLtiProvider($params['ltiProvider']);
        $consumerKey = $ltiProvider->getKey();
        $consumerSecret = $ltiProvider->getSecret();

        $urlHelper = $this->getUrlHelper();

        $returnUrl = $urlHelper->buildUrl('index', 'DeliveryServer', 'taoDelivery');
        $outcomeServiceUrl = $urlHelper->buildUrl('manageResults', 'ResultController', 'taoLtiConsumer');

        $data = [
            LtiLaunchData::LTI_MESSAGE_TYPE => 'basic-lti-launch-request',
            LtiLaunchData::LTI_VERSION => 'LTI-1p0',
            LtiLaunchData::RESOURCE_LINK_ID => $execution->getIdentifier(),
            LtiLaunchData::USER_ID => $this->getServiceLocator()->get(SessionService::SERVICE_ID)->getCurrentUser()->getIdentifier(),
            LtiLaunchData::ROLES => 'Learner',
            LtiLaunchData::LAUNCH_PRESENTATION_RETURN_URL => $returnUrl,
            LtiLaunchData::LIS_RESULT_SOURCEDID => $execution->getIdentifier(),
            LtiLaunchData::LIS_OUTCOME_SERVICE_URL => $outcomeServiceUrl,
        ];
        $data = ToolConsumer::addSignature($ltiUrl, $consumerKey, $consumerSecret, $data);

        $container = new LtiExecutionContainer($execution);
        $container->setData('launchUrl', $ltiUrl);
        $container->setData('launchParams', $data);

        $this->logDebug(
            sprintf('** taoLtiConsumer: preparing http call :: to the %s, with payload %s **',
                $ltiUrl,
                json_encode($this->getAnonimizerHelper()->anonymize($data))));
        return $container;
    }

    /**
     * @return UrlHelper
     */
    protected function getUrlHelper()
    {
        return $this->getServiceLocator()->get(UrlHelper::class);
    }

    /**
     * @param string $id
     *
     * @return LtiProvider
     */
    private function getLtiProvider($id)
    {
        return $this->getServiceLocator()->get(LtiProviderService::class)->searchById($id);
    }


    /**
     * @return UrlHelper
     */
    protected function getUrlHelper()
    {
        return $this->getServiceLocator()->get(UrlHelper::class);
    }

    /**
     * @return AnonymizeHelper
     */
    private function getAnonimizerHelper()
    {
        return new AnonymizeHelper([AnonymizeHelper::OPTION_BLACK_LIST => ['oauth_consumer_key']]);
    }
}

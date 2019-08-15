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

use common_exception_InvalidArgumentType;
use common_exception_NotFound;
use oat\taoDelivery\model\container\delivery\AbstractContainer;
use oat\taoDelivery\model\container\execution\ExecutionClientContainer;
use oat\taoDelivery\model\container\ExecutionContainer;
use oat\taoDelivery\model\execution\DeliveryExecution;
use IMSGlobal\LTI\ToolProvider\ToolConsumer;
use oat\oatbox\session\SessionService;
use oat\generis\model\OntologyAwareTrait;
use oat\taoLtiConsumer\model\credentials\CredentialsProviderFactory;
use oat\taoLtiConsumer\model\credentials\CredentialsProviderInterface;
use oat\taoLtiConsumer\model\credentials\RdfCredentialsProvider;

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
     * @throws common_exception_NotFound
     */
    public function getExecutionContainer(DeliveryExecution $execution)
    {
        $params = $this->getRuntimeParams();
        $ltiUrl = $params['ltiPath'];

        $credentialsProvider = $this->getCredentialsProvider($params);
        $consumerKey = $credentialsProvider->getConsumerKey();
        $consumerSecret = $credentialsProvider->getConsumerSecret();

        $returnUrl = _url('index', 'DeliveryServer', 'taoDelivery');

        $data = [
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',
            'resource_link_id' => $execution->getDelivery()->getUri(),
            'user_id' => $this->getServiceLocator()->get(SessionService::SERVICE_ID)->getCurrentUser()->getIdentifier(),
            'roles' => 'Learner',
            'launch_presentation_return_url' => $returnUrl,
            'lis_result_sourcedid' => $execution->getIdentifier(),
        ];
        $data = ToolConsumer::addSignature($ltiUrl, $consumerKey, $consumerSecret, $data);

        $container = new LtiExecutionContainer($execution);
        $container->setData('launchUrl', $ltiUrl);
        $container->setData('launchParams', $data);

        return $container;
    }

    /**
     * Loads credentials provider based on container, have fallback to default one
     * @param array $params
     * @return CredentialsProviderInterface
     */
    private function getCredentialsProvider(array $params)
    {
        $providerName = RdfCredentialsProvider::class;
        $providerId = isset($params['ltiProvider']) ? $params['ltiProvider'] : null;

        if (isset($params['credentialsProviderClass'])) {
            $providerName = $params['credentialsProviderClass'];
            $providerId = $params['credentialsProviderId'];
        }

        return CredentialsProviderFactory::getProvider($providerName, $providerId);
    }
}

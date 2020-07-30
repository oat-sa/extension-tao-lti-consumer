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
use oat\taoDelivery\model\container\delivery\AbstractContainer;
use oat\taoDelivery\model\container\execution\ExecutionClientContainer;
use oat\taoDelivery\model\container\ExecutionContainer;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoLti\models\tool\launch\LtiLaunchInterface;
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
        $launch = $this->proxyLtiLaunch($execution);

        $container = new LtiExecutionContainer($execution);
        $container->setData('launchUrl', $launch->getToolLaunchUrl());
        $container->setData('launchParams', $launch->getToolLaunchParams());

        $this->logDebug(
            sprintf(
                '** taoLtiConsumer: preparing http call :: to the %s, with payload %s **',
                $launch->getToolLaunchUrl(),
                json_encode($this->getAnonimizerHelper()->anonymize($launch->getToolLaunchParams()))
            )
        );

        return $container;
    }

    private function proxyLtiLaunch(DeliveryExecution $execution): LtiLaunchInterface
    {
        $params = $this->getRuntimeParams();
        $ltiProvider = $this->getLtiProvider($params['ltiProvider']);

        return $this->getServiceLocator()
            ->get(LtiDeliveryLaunchProxy::class)
            ->launch(
                $params['ltiPath'],
                $ltiProvider,
                $execution
            );
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
     * @return AnonymizeHelper
     */
    private function getAnonimizerHelper()
    {
        return new AnonymizeHelper([AnonymizeHelper::OPTION_BLACK_LIST => ['oauth_consumer_key']]);
    }
}

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
 * Copyright (c) 2019-2020 (original work) Open Assessment Technologies SA
 */

declare(strict_types=1);

namespace oat\taoLtiConsumer\model\delivery\container;

use oat\generis\model\OntologyAwareTrait;
use oat\taoDeliverConnect\model\delivery\factory\RemoteDeliveryFactory;
use oat\taoDelivery\model\container\delivery\AbstractContainer;
use oat\taoDelivery\model\container\execution\ExecutionClientContainer;
use oat\taoDelivery\model\container\ExecutionContainer;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;
use oat\taoLti\models\classes\Tool\Factory\LtiLaunchCommandFactoryInterface;
use oat\taoLti\models\classes\Tool\Service\LtiLauncherInterface;
use oat\taoLti\models\classes\Tool\Service\LtiLauncherProxy;
use oat\taoLtiConsumer\model\AnonymizeHelper;
use oat\taoLtiConsumer\model\Tool\Factory\LtiDeliveryLaunchCommandFactoryProxy;

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
        $launchUrl = $params['ltiPath'];
        $ltiProvider = $this->getLtiProvider($params['ltiProvider']);

        $config = [
            'launchUrl' => $launchUrl,
            'ltiProvider' => $ltiProvider,
            'deliveryExecution' => $execution,
        ];

        $command = $this->getLtiLaunchCommandFactory()->create($config);
        $launch = $this->getLtiLauncher()->launch($command);

        $container = new LtiExecutionContainer($execution);
        $container->setData('launchUrl', $launch->getToolLaunchUrl());
        $container->setData('launchParams', $launch->getToolLaunchParams());

        $this->logDebug(
            sprintf(
                '** taoLtiConsumer: preparing http call :: to the %s, with payload %s **',
                $launchUrl,
                json_encode(
                    $this->getAnonimizerHelper()->anonymize($launch->getToolLaunchParams())
                )
            )
        );

        return $container;
    }

    private function getLtiProvider(string $id): LtiProvider
    {
        return $this->getServiceLocator()->get(LtiProviderService::class)->searchById($id);
    }

    private function getAnonimizerHelper(): AnonymizeHelper
    {
        return new AnonymizeHelper([AnonymizeHelper::OPTION_BLACK_LIST => ['oauth_consumer_key']]);
    }

    private function getLtiLaunchCommandFactory(): LtiLaunchCommandFactoryInterface
    {
        return $this->getServiceLocator()->get(LtiDeliveryLaunchCommandFactoryProxy::class);
    }

    private function getLtiLauncher(): LtiLauncherInterface
    {
        return $this->getServiceLocator()->get(LtiLauncherProxy::class);
    }
}

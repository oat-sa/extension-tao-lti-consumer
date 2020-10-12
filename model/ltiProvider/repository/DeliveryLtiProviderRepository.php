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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoLtiConsumer\model\ltiProvider\repository;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecutionService;
use oat\taoDeliveryRdf\model\ContainerRuntime;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\LtiProvider\LtiProviderRepositoryInterface;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;

class DeliveryLtiProviderRepository extends ConfigurableService
{
    use OntologyAwareTrait;

    public function searchByDeliveryExecutionId(string $deliveryExecutionId): LtiProvider
    {
        $delivery = $this->getDeliveryExecutionService()
            ->getDeliveryExecution($deliveryExecutionId)
            ->getDelivery();

        $containerJson = $containerJson = json_decode(
            (string)$delivery->getOnePropertyValue(
                $this->getProperty(ContainerRuntime::PROPERTY_CONTAINER)
            ),
            true
        );

        if (empty($containerJson['params']['ltiProvider'])) {
            throw new LtiException('This delivery does not contain required lti provider defined');
        }

        return $this->getLtiProvider()->searchById($containerJson['params']['ltiProvider']);
    }

    private function getDeliveryExecutionService(): DeliveryExecutionService
    {
        return $this->getServiceLocator()->get(DeliveryExecutionService::SERVICE_ID);
    }

    private function getLtiProvider(): LtiProviderRepositoryInterface
    {
        return $this->getServiceLocator()->get(LtiProviderService::SERVICE_ID);
    }
}

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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoLtiConsumer\model;

use common_exception_NotFound;
use core_kernel_classes_Resource;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\KVDeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;

/**
 * This straightforward implementation doesn't perform any check on $ltiProvider
 */
class BaseDeliveryExecutionGetter extends ConfigurableService implements DeliveryExecutionGetterInterface
{
    use OntologyAwareTrait;

    /**
     * Due to multiple implementation of DE storages it's difficult to check if DE exists
     * Ontology and KV storages allow us to check exists() but for other ones we have to try
     * to read mandatory 'status' property
     * @param string $deliveryExecutionId
     * @param LtiProvider $ltiProvider
     * @return DeliveryExecutionInterface|null
     */
    public function get($deliveryExecutionId, LtiProvider $ltiProvider)
    {
        $deliveryExecution = $this->getServiceProxy()->getDeliveryExecution($deliveryExecutionId);
        return $this->isExists($deliveryExecution)
            ? $deliveryExecution
            : null;
    }

    /**
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return bool
     */
    protected function isExists(DeliveryExecutionInterface $deliveryExecution)
    {
        if ($deliveryExecution instanceof core_kernel_classes_Resource ||
            $deliveryExecution instanceof KVDeliveryExecution
        ) {
            return $deliveryExecution->exists();
        }

        try {
            $state = $deliveryExecution->getState();
            return $state !== null && !empty($state->getUri());
        } catch (common_exception_NotFound $notFoundException) {
            return false;
        }
    }

    /**
     * @return ServiceProxy
     */
    protected function getServiceProxy()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(ServiceProxy::SERVICE_ID);
    }
}

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
use oat\taoDelivery\model\execution\ServiceProxy;

class DeliveryExecutionGetter extends ConfigurableService
{
    use OntologyAwareTrait;

    /**
     * Duplicated to not to create direct dependency between extensions
     * @see \oat\taoDeliverConnect\model\delivery\factory\RemoteDeliveryFactory::PROPERTY_TENANT_ID
     */
    public const PROPERTY_TENANT_ID = 'http://www.tao.lu/Ontologies/taoDeliverConnect.rdf#TenantId';

    /**
     * Due to multiple implementation of DE storages it's difficult to check if DE exists
     * Ontology storage allows us to check exists() but for other storages we have to try
     * to read mandatory 'status' property
     * @param string $deliveryExecutionId
     * @param string|null $tenantId if not null require delivery execution to belong to specified tenant
     * @return DeliveryExecutionInterface|null
     */
    public function get($deliveryExecutionId, $tenantId)
    {
        $deliveryExecution = $this->getServiceProxy()->getDeliveryExecution($deliveryExecutionId);
        if ($deliveryExecution instanceof core_kernel_classes_Resource) {
            if (!$deliveryExecution->exists()) {
                return null;
            }
        } else {
            try {
                $state = $deliveryExecution->getState();
                if (empty($state->getUri())) {
                    return null;
                }
            } catch (common_exception_NotFound $notFoundException) {
                return null;
            }
        }

        if ($tenantId !== null && !$this->checkHasTenantId($deliveryExecution, $tenantId)) {
            return null;
        }

        return $deliveryExecution;
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * @param DeliveryExecutionInterface $deliveryExecution
     * @param string $tenantId
     * @return bool
     */
    private function checkHasTenantId(DeliveryExecutionInterface $deliveryExecution, $tenantId)
    {
        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $deTenantId = (string) $deliveryExecution->getDelivery()->getOnePropertyValue(
                $this->getProperty(self::PROPERTY_TENANT_ID)
            );
        } catch (common_exception_NotFound $exception) {
            return false;
        }

        return $deTenantId === $tenantId;
    }

    /**
     * @return ServiceProxy
     */
    private function getServiceProxy()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(ServiceProxy::SERVICE_ID);
    }
}

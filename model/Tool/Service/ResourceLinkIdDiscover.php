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

declare(strict_types=1);

namespace oat\taoLtiConsumer\model\Tool\Service;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoDeliverConnect\model\delivery\factory\RemoteDeliveryFactory;
use oat\taoDeliverConnect\model\TenantLtiProvider;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;

class ResourceLinkIdDiscover extends ConfigurableService implements ResourceLinkIdDiscoverInterface
{
    use OntologyAwareTrait;

    public function discoverByDeliveryExecutionAndLtiProvider(
        DeliveryExecution $execution,
        LtiProvider $ltiProvider
    ): string
    {
        if ($ltiProvider instanceof TenantLtiProvider) {
            return (string)$execution->getDelivery()
                ->getUniquePropertyValue($this->getProperty(RemoteDeliveryFactory::PROPERTY_PUBLISHED_DELIVERY_ID));
        }

        return $execution->getIdentifier();
    }
}

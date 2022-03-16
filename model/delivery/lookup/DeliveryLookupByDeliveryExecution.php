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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA ;
 */

declare(strict_types=1);

namespace oat\taoLtiConsumer\model\delivery\lookup;

use oat\taoDelivery\model\execution\DeliveryExecutionService;

class DeliveryLookupByDeliveryExecution implements DeliveryLookupInterface
{
    /** @var DeliveryExecutionService  */
    private $deliveryExecutionService;

    public function __construct(DeliveryExecutionService $deliveryExecutionService)
    {
        $this->deliveryExecutionService = $deliveryExecutionService;
    }

    public function searchBySourcedId(string $sourcedId)
    {
        $deliveryExecution = $this->deliveryExecutionService->getDeliveryExecution($sourcedId);
        if (!$deliveryExecution->exists()) {
            return null;
        }

        return $deliveryExecution->getDelivery();
    }
}

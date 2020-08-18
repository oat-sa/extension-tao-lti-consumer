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
 * Copyright (c) 2019-2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoLtiConsumer\model;

use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;

interface DeliveryExecutionGetterInterface
{
    public const SERVICE_ID = 'taoLtiConsumer/DeliveryExecutionGetter';

    /**
     * Retrieves delivery execution by id (or returns null if doesn't exist) accessible by passed $ltiProvider
     * @return DeliveryExecutionInterface|null returns null if DE doesn't exist
     */
    public function get(string $deliveryExecutionId, LtiProvider $ltiProvider): ?DeliveryExecutionInterface;
}

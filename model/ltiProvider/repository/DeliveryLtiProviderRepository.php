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

use oat\generis\model\data\Ontology;
use oat\taoDeliveryRdf\model\ContainerRuntime;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\LtiProvider\LtiProviderRepositoryInterface;
use oat\taoLtiConsumer\model\delivery\lookup\DeliveryLookupInterface;

class DeliveryLtiProviderRepository
{
    /** @var LtiProviderRepositoryInterface */
    private $ltiProviderRepository;

    /** @var Ontology  */
    private $ontology;

    /** @var array */
    private $deliveryLookupProviders;

    public function __construct(
        LtiProviderRepositoryInterface $ltiProviderRepository,
        Ontology $ontology,
        array $deliveryLookupProviders
    ) {
        $this->ltiProviderRepository = $ltiProviderRepository;
        $this->ontology = $ontology;
        $this->deliveryLookupProviders = $deliveryLookupProviders;
    }

    public function searchBySourcedId(string $sourcedId): LtiProvider
    {
        $delivery = $this->findDelivery($sourcedId);

        $containerJson = json_decode(
            (string)$delivery->getOnePropertyValue(
                $this->ontology->getProperty(ContainerRuntime::PROPERTY_CONTAINER)
            ),
            true
        );

        if (empty($containerJson['params']['ltiProvider'])) {
            throw new LtiException('This delivery does not contain required lti provider defined');
        }

        return $this->ltiProviderRepository->searchById($containerJson['params']['ltiProvider']);
    }

    private function findDelivery(string $sourcedId)
    {
        /** @var DeliveryLookupInterface $provider */
        foreach ($this->deliveryLookupProviders as $provider) {
            if ($delivery = $provider->searchBySourcedId($sourcedId)) {
                return $delivery;
            }
        }

        throw new LtiException('Could not find delivery for provided sourcedId');
    }
}

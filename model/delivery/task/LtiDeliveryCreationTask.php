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
 *
 */

namespace oat\taoLtiConsumer\model\delivery\task;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\AbstractAction;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoLtiConsumer\model\delivery\factory\LtiDeliveryFactory;
use common_report_Report as Report;
use common_exception_MissingParameter as MissingParameter;

class LtiDeliveryCreationTask extends AbstractAction implements \JsonSerializable
{
    use OntologyAwareTrait;

    /**
     * Task to create LTI based delivery
     *
     * The only responsability of this task is to parse parameters and forward request to LtiDeliveryFactory
     *
     * @param array $params
     * @throws \common_exception_MissingParameter
     * @throws \common_exception_InconsistentData
     * @return Report
     */
    public function __invoke($params)
    {
        if (!isset($params['ltiProvider'])) {
            throw new MissingParameter('Missing parameter `ltiProvider` in ' . self::class);
        }

        if (!isset($params['ltiPath'])) {
            throw new MissingParameter('Missing parameter `ltiPath` in ' . self::class);
        }

        if (isset($params['deliveryClass'])) {
            $deliveryClass = $this->getClass($params['deliveryClass']);
            if (!$deliveryClass->exists()) {
                $deliveryClass = $this->getClass(DeliveryAssemblyService::CLASS_URI);
            }
        } else {
            $deliveryClass = $this->getClass(DeliveryAssemblyService::CLASS_URI);
        }

        $ltiProvider = $this->getResource($params['ltiProvider']);
        $ltiPath = $params['ltiProvider'];
        $label = isset($params['label']) ? $params['label'] : '';
        $deliveryResource = isset($params['deliveryResource']) ? $this->getResource($params['deliveryResource']) : null;

        /** @var Report $report */
        $report = $this->getLtiDeliveryFactory()->create(
            $deliveryClass, $ltiProvider, $ltiPath, $label, $deliveryResource
        );

        if ($report->getType() === Report::TYPE_ERROR) {
            $deliveryResource->delete(true);
        }

        return $report;
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return __CLASS__;
    }

    /**
     * @return LtiDeliveryFactory
     */
    protected function getLtiDeliveryFactory()
    {
        return $this->getServiceLocator()->get(LtiDeliveryFactory::class);
    }
}

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

use core_kernel_classes_Class as RdfClass;
use common_exception_InconsistentData as InconsistentDataException;
use JsonSerializable;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\AbstractAction;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;
use oat\taoLtiConsumer\model\delivery\factory\LtiDeliveryFactory;
use common_report_Report as Report;
use common_exception_MissingParameter as MissingParameterException;

class LtiDeliveryCreationTask extends AbstractAction implements JsonSerializable
{
    use OntologyAwareTrait;

    /**
     * Task to create LTI based delivery
     *
     * The only responsibility of this task is to parse parameters and forward request to LtiDeliveryFactory
     *
     * @param array $params
     * @return Report
     *@throws InconsistentDataException
     * @throws MissingParameterException
     */
    public function __invoke($params)
    {
        if (!isset($params['ltiProvider'])) {
            throw new MissingParameterException('ltiProvider', self::class);
        }

        if (!isset($params['ltiPath'])) {
            throw new MissingParameterException('ltiPath', self::class);
        }

        $deliveryClass = $this->getDeliveryClass($params);

        $ltiProvider = $this->getLtiProviderService()->searchById($params['ltiProvider']);
        $ltiPath = $params['ltiPath'];
        $label = isset($params['label']) ? $params['label'] : '';
        $deliveryResource = isset($params['deliveryResource'])? $this->getResource($params['deliveryResource']) : null;

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

    /**
     * @return LtiProviderService
     */
    protected function getLtiProviderService()
    {
        return $this->getServiceLocator()->get(LtiProviderService::class);
    }

    /**
     * @param $params
     *
     * @return RdfClass
     */
    protected function getDeliveryClass($params)
    {
        if (isset($params['deliveryClass'])) {
            $deliveryClass = $this->getClass($params['deliveryClass']);
            if ($deliveryClass->exists()) {
                return $deliveryClass;
            }
        }

        return $this->getClass(DeliveryAssemblyService::CLASS_URI);
    }
}

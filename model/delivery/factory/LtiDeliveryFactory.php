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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoLtiConsumer\model\delivery\factory;

use oat\generis\model\OntologyRdfs;
use oat\taoLtiConsumer\model\delivery\task\LtiDeliveryCreationTask;
use oat\oatbox\event\EventManager;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\taskQueue\QueueDispatcher;
use oat\taoDelivery\model\container\delivery\DeliveryContainerRegistry;
use oat\taoDeliveryRdf\model\ContainerRuntime;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;

/**
 * Class LtiDeliveryFactory
 *
 * A factory to create LTI based delivery, this creation can done in a deferred way
 *
 * @package oat\taoLtiConsumer\model\delivery\factory
 */
class LtiDeliveryFactory extends ConfigurableService
{
    use LoggerAwareTrait;

    /**
     * Create a LTI based delivery under $delvieryClass with $provider & $ltiPath
     *
     * @param \core_kernel_classes_Class $deliveryClass
     * @param \core_kernel_classes_Resource $ltiProvider
     * @param $ltiPath
     * @param string $label
     * @param \core_kernel_classes_Resource|null $deliveryResource
     * @return \common_report_Report
     * @throws \common_exception_InconsistentData
     */
    public function create(
        \core_kernel_classes_Class $deliveryClass,
        \core_kernel_classes_Resource $ltiProvider,
        $ltiPath,
        $label = '',
        \core_kernel_classes_Resource $deliveryResource = null
    ) {
        $this->logInfo(sprintf(
            'Creating LTI delivery with LTI provider "%s" '. 'with LTI test url "%s" under delivery class "%s"',
            $ltiProvider->getLabel(), $ltiPath, $deliveryClass->getLabel()
        ));

        $container = $this->getLtiDeliveryContainer($ltiProvider, $ltiPath);

        if ($label == '') {
            $label = 'LTI delivery ' . ($deliveryClass->countInstances()+1);
        }

        $properties = [
            OntologyRdfs::RDFS_LABEL => $label,
            DeliveryAssemblyService::PROPERTY_DELIVERY_TIME => time(),
            ContainerRuntime::PROPERTY_CONTAINER => json_encode($container),
        ];

        if (!$deliveryResource instanceof \core_kernel_classes_Resource) {
            $deliveryResource = $deliveryClass->createInstanceWithProperties($properties);
        } else {
            $deliveryResource->setPropertiesValues($properties);
        }

        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        $eventManager->trigger(new DeliveryCreatedEvent($deliveryResource->getUri()));

        return new \common_report_Report(
            \common_report_Report::TYPE_SUCCESS,
            __('LTI delivery successfully created.'),
            $deliveryResource
        );
    }

    /**
     * Create a task for LTI delivery creation
     *
     * @param \core_kernel_classes_Class $deliveryClass
     * @param \core_kernel_classes_Resource $ltiProvider
     * @param $ltiPath
     * @param string $label
     * @param \core_kernel_classes_Resource|null $deliveryResource
     * @return mixed
     */
    public function deferredCreate(
        \core_kernel_classes_Class $deliveryClass,
        \core_kernel_classes_Resource $ltiProvider,
        $ltiPath,
        $label = '',
        \core_kernel_classes_Resource $deliveryResource = null
    ) {
        $action = new LtiDeliveryCreationTask();
        $parameters = [
            'deliveryClass' => $deliveryClass->getUri(),
            'ltiProvider' => $ltiProvider->getUri(),
            'ltiPath' => $ltiPath,
            'label' => $label,
            'deliveryResource' => is_null($deliveryResource) ? null : $deliveryResource->getUri()
        ];

        return $this->getServiceLocator()
            ->get(QueueDispatcher::SERVICE_ID)
            ->createTask($action, $parameters, __('Publishing of LTI delivery : "%s"', $ltiProvider->getLabel()), null, true);
    }

    /**
     * Retrieve the delivery container associated to LTI
     *
     * @param \core_kernel_classes_Resource $ltiProvider
     * @param $ltiPath
     * @return string
     * @throws \common_exception_InconsistentData
     */
    protected function getLtiDeliveryContainer(\core_kernel_classes_Resource $ltiProvider, $ltiPath)
    {
        /** @var DeliveryContainerRegistry $registry */
        $registry = $this->propagate(DeliveryContainerRegistry::getRegistry());
        return $registry->getDeliveryContainer('lti', [
            'ltiProvider' => $ltiProvider,
            'ltiPath' => $ltiPath
        ]);
    }
}

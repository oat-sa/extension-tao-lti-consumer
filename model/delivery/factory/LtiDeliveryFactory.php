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

use common_exception_InconsistentData as InconsistentDataException;
use common_report_Report as Report;
use core_kernel_classes_Resource as RdfResource;
use core_kernel_classes_Class as RdfClass;
use oat\generis\model\OntologyRdfs;
use oat\tao\model\taskQueue\Task\TaskInterface;
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
 */
class LtiDeliveryFactory extends ConfigurableService
{
    use LoggerAwareTrait;

    /**
     * Create a LTI based delivery under $delvieryClass with $provider & $ltiPath
     *
     * @param RdfClass $deliveryClass
     * @param RdfResource $ltiProvider
     * @param string $ltiPath
     * @param string $label
     * @param RdfResource|null $deliveryResource
     *
     * @return Report
     * @throws InconsistentDataException
     */
    public function create(
        RdfClass $deliveryClass,
        RdfResource $ltiProvider,
        $ltiPath,
        $label = '',
        RdfResource $deliveryResource = null
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

        if ($deliveryResource instanceof RdfResource) {
            $deliveryResource->setPropertiesValues($properties);
        } else {
            $deliveryResource = $deliveryClass->createInstanceWithProperties($properties);
        }

        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        $eventManager->trigger(new DeliveryCreatedEvent($deliveryResource->getUri()));

        return new Report(
            Report::TYPE_SUCCESS,
            __('LTI delivery successfully created.'),
            $deliveryResource
        );
    }

    /**
     * Create a task for LTI delivery creation
     *
     * @param RdfClass $deliveryClass
     * @param RdfResource $ltiProvider
     * @param string $ltiPath
     * @param string $label
     * @param RdfResource|null $deliveryResource
     *
     * @return TaskInterface
     */
    public function deferredCreate(
        RdfClass $deliveryClass,
        RdfResource $ltiProvider,
        $ltiPath,
        $label = '',
        RdfResource $deliveryResource = null
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
     * @param RdfResource $ltiProvider
     * @param $ltiPath
     * @return string
     * @throws InconsistentDataException
     */
    private function getLtiDeliveryContainer(RdfResource $ltiProvider, $ltiPath)
    {
        /** @var DeliveryContainerRegistry $registry */
        $registry = $this->propagate(DeliveryContainerRegistry::getRegistry());
        return $registry->getDeliveryContainer('lti', [
            'ltiProvider' => $ltiProvider->getUri(),
            'ltiPath' => $ltiPath
        ]);
    }
}

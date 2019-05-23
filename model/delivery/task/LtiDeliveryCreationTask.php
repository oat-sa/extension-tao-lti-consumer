<?php
/**
 * Created by PhpStorm.
 * User: siwane
 * Date: 15/05/19
 * Time: 21:43
 */

namespace oat\taoLtiConsumer\model\delivery\task;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\AbstractAction;
use oat\tao\model\taskQueue\Task\TaskAwareInterface;
use oat\tao\model\taskQueue\Task\TaskAwareTrait;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoLtiConsumer\model\delivery\factory\LtiDeliveryFactory;

class LtiDeliveryCreationTask extends AbstractAction implements \JsonSerializable
{
//    use TaskAwareTrait;
    use OntologyAwareTrait;

    /**
     * @param $params
     * @throws \common_exception_MissingParameter
     * @return Report
     */
    public function __invoke($params)
    {
        if (!isset($params['ltiProvider'])) {
            throw new \common_exception_MissingParameter('Missing parameter `ltiProvider` in ' . self::class);
        }

        if (!isset($params['ltiPath'])) {
            throw new \common_exception_MissingParameter('Missing parameter `ltiPath` in ' . self::class);
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

        if ($report->getType() === \common_report_Report::TYPE_ERROR ) {
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

    protected function getLtiDeliveryFactory()
    {
        return $this->getServiceLocator()->get(LtiDeliveryFactory::class);
    }
}
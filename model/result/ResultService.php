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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA
 */
namespace oat\taoLtiConsumer\model\result;

use common_exception_Error;
use common_exception_NotFound;
use core_kernel_classes_Resource;
use oat\oatbox\event\EventManager;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoLtiConsumer\model\result\event\ResultReadyEvent;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequest;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseInterface;
use oat\taoLtiConsumer\model\result\operations\replace\OperationRequest as ReplaceOperationRequest;
use oat\taoLtiConsumer\model\result\operations\unsupported\Response as UnsupportedResponse;
use oat\taoLtiConsumer\model\result\operations\replace\Response as ReplaceResponse;

class ResultService extends ConfigurableService
{
    public const SERVICE_ID = 'taoLtiConsumer/resultService';

    /**
     * @param LisOutcomeRequest $request
     * @return LisOutcomeResponseInterface
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    public function process($request)
    {
        $operationRequest = $request->getOperation();
        if ($operationRequest instanceof ReplaceOperationRequest) {
            return $this->handleReplaceRequest($request);
        }

        return $this->handleUnsupportedRequest($request);
    }

    /**
     * @param LisOutcomeRequest $request
     * @return UnsupportedResponse
     */
    protected function handleUnsupportedRequest(LisOutcomeRequest $request)
    {
        return new UnsupportedResponse(
            $request->getOperationName(),
            null,
            $request->getMessageIdentifier(),
            $request->getOperationName()
        );
    }

    /**
     * @param LisOutcomeRequest $request
     * @return ReplaceResponse
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    protected function handleReplaceRequest(LisOutcomeRequest $request)
    {
        /** @var ReplaceOperationRequest $operationRequest */
        $operationRequest = $request->getOperation();
        $deliveryExecution = $this->getDeliveryExecution($operationRequest->getSourcedId());
        if ($deliveryExecution === null) {
            return new ReplaceResponse(
                ReplaceResponse::STATUS_NOT_FOUND,
                sprintf('Delivery execution "%s" not found', $operationRequest->getSourcedId()),
                ReplaceResponse::CODE_MAJOR_FAILURE,
                null,
                $request->getMessageIdentifier(),
                $request->getOperationName()
            );
        }
        // don't check return value, ignore the case when exact the same score variable exists
        // because variables considered as equal only if their's epoch (microtime()) are the same
        $this->getScoreWriter()->store($deliveryExecution, $operationRequest->getScore());

        /** @var EventManager $eventManager*/
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        $eventManager->trigger(new ResultReadyEvent($deliveryExecution->getIdentifier()));

        return new ReplaceResponse(
            ReplaceResponse::STATUS_SUCCESS,
            sprintf('Score for %s is now %s', $operationRequest->getSourcedId(), $operationRequest->getScore()),
            ReplaceResponse::CODE_MAJOR_SUCCESS,
            null,
            $request->getMessageIdentifier(),
            $request->getOperationName()
        );
    }

    /**
     * Due to multiple implementation of DE storages it's difficult to check if DE exists
     * Ontology storage allows us to check exists() but for other storages we have to try
     * to read mandatory 'status' property
     * @param string $deliveryExecutionId
     * @return DeliveryExecutionInterface|null
     */
    private function getDeliveryExecution($deliveryExecutionId)
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

        return $deliveryExecution;
    }

    /**
     * @return ServiceProxy
     */
    private function getServiceProxy()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(ServiceProxy::SERVICE_ID);
    }

    /**
     * @return ScoreWriterService
     */
    private function getScoreWriter()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(ScoreWriterService::class);
    }
}

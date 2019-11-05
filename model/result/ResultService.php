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
use LogicException;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\event\EventManager;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLtiConsumer\model\DeliveryExecutionGetter;
use oat\taoLtiConsumer\model\result\event\ResultReadyEvent;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequest;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseInterface;
use oat\taoLtiConsumer\model\result\operations\BasicResponse;
use oat\taoLtiConsumer\model\result\operations\failure\Response as FailureResponse;
use oat\taoLtiConsumer\model\result\operations\replace\OperationRequest as ReplaceOperationRequest;
use oat\taoLtiConsumer\model\result\operations\replace\Response as ReplaceResponse;

class ResultService extends ConfigurableService
{
    use OntologyAwareTrait;

    public const SERVICE_ID = 'taoLtiConsumer/resultService';

    /**
     * @param LisOutcomeRequest $request
     * @param string|null $tenantId if not null check that delivery execution belongs to specified tenant
     * @return LisOutcomeResponseInterface
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    public function process(LisOutcomeRequest $request, $tenantId)
    {
        $operationRequest = $request->getOperation();
        if ($operationRequest === null) {
            return $this->getUnsupportedOperationResponse($request);
        }

        $deliveryExecution = $this->getDeliveryExecutionGetter()->get($operationRequest->getSourcedId(), $tenantId);
        if ($deliveryExecution === null) {
            return $this->getDeliveryNotFoundResponse($request, $operationRequest->getSourcedId(), $tenantId);
        }

        if ($operationRequest instanceof ReplaceOperationRequest) {
            return $this->handleReplaceRequest($request, $deliveryExecution);
        }

        throw new LogicException('Wrong operation request: ' . $request->getOperationName());
    }

    /**
     * @param LisOutcomeRequest $request
     * @return BasicResponse
     */
    protected function getUnsupportedOperationResponse(LisOutcomeRequest $request)
    {
        return new BasicResponse(
            BasicResponse::STATUS_UNSUPPORTED,
            sprintf('%s is not supported', $request->getOperationName()),
            BasicResponse::CODE_MAJOR_UNSUPPORTED,
            null,
            $request->getMessageIdentifier(),
            $request->getOperationName()
        );
    }

    /**
     * @param LisOutcomeRequest $request
     * @param string $sourcedId
     * @param string|null $tenantId
     * @return FailureResponse
     */
    protected function getDeliveryNotFoundResponse(LisOutcomeRequest $request, $sourcedId, $tenantId)
    {
        $statusDescription = $tenantId !== null
            ? "Delivery execution '$sourcedId' for tenant id '$tenantId' not found"
            : "Delivery execution '$sourcedId' not found";

        return new FailureResponse(
            $request->getOperationName(),
            BasicResponse::STATUS_NOT_FOUND,
            $statusDescription,
            BasicResponse::CODE_MAJOR_FAILURE,
            null,
            $request->getMessageIdentifier(),
            $request->getOperationName()
        );
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * @param LisOutcomeRequest $request
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return ReplaceResponse
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    protected function handleReplaceRequest(LisOutcomeRequest $request, DeliveryExecutionInterface $deliveryExecution)
    {
        /** @var ReplaceOperationRequest $operationRequest */
        $operationRequest = $request->getOperation();

        // don't check return value, ignore the case when exact the same score variable exists
        // because variables considered as equal only if their's epoch (microtime()) are the same
        $this->getScoreWriter()->store($deliveryExecution, $operationRequest->getScore());

        /** @var EventManager $eventManager*/
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        /** @noinspection PhpUnhandledExceptionInspection */
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
     * @return ScoreWriterService
     */
    private function getScoreWriter()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(ScoreWriterService::class);
    }

    /**
     * @return DeliveryExecutionGetter
     */
    private function getDeliveryExecutionGetter()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(DeliveryExecutionGetter::class);
    }
}

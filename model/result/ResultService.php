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

declare(strict_types=1);

namespace oat\taoLtiConsumer\model\result;

use common_exception_Error;
use common_exception_NotFound;
use LogicException;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\event\EventManager;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\StateServiceInterface;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLtiConsumer\model\DeliveryExecutionGetterInterface;
use oat\taoLtiConsumer\model\result\event\LisScoreReceivedEvent;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequest;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseInterface;
use oat\taoLtiConsumer\model\result\operations\BasicResponse;
use oat\taoLtiConsumer\model\result\operations\failure\Response as FailureResponse;
use oat\taoLtiConsumer\model\result\operations\replace\OperationRequest as ReplaceOperationRequest;
use oat\taoLtiConsumer\model\result\operations\replace\Response as ReplaceResponse;

class ResultService extends ConfigurableService
{
    use OntologyAwareTrait;
    use LoggerAwareTrait;

    public const SERVICE_ID = 'taoLtiConsumer/resultService';

    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    public function process(LisOutcomeRequest $request, LtiProvider $ltiProvider): LisOutcomeResponseInterface
    {
        $operationRequest = $request->getOperation();
        if ($operationRequest === null) {
            $this->logWarning(sprintf(
                'The requested "%s" operation is not currently supported in our platform',
                $request->getOperationName()
            ));
            return $this->getUnsupportedOperationResponse($request);
        }

        $deliveryExecution = $this->getDeliveryExecutionGetter()
            ->get($operationRequest->getSourcedId(), $ltiProvider);
        if ($deliveryExecution === null) {
            $this->logWarning(
                sprintf(
                    "Delivery execution '%s' not found during '%s' operation processing for ltiProvider with key '%s': ",
                    $operationRequest->getSourcedId(),
                    $request->getOperationName(),
                    $ltiProvider->getKey()
                )
            );
            return $this->getDeliveryExecutionNotFoundResponse($request, $operationRequest->getSourcedId());
        }

        if ($operationRequest instanceof ReplaceOperationRequest) {
            return $this->handleReplaceRequest($request, $deliveryExecution);
        }

        throw new LogicException('Wrong operation request: ' . $request->getOperationName());
    }

    private function getUnsupportedOperationResponse(LisOutcomeRequest $request): BasicResponse
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

    private function getDeliveryExecutionNotFoundResponse(LisOutcomeRequest $request, string $sourcedId): FailureResponse
    {
        return new FailureResponse(
            $request->getOperationName(),
            BasicResponse::STATUS_NOT_FOUND,
            "Delivery execution '$sourcedId' not found",
            BasicResponse::CODE_MAJOR_FAILURE,
            null,
            $request->getMessageIdentifier(),
            $request->getOperationName()
        );
    }

    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    private function handleReplaceRequest(LisOutcomeRequest $request, DeliveryExecutionInterface $deliveryExecution): ReplaceResponse
    {
        /** @var ReplaceOperationRequest $operationRequest */
        $operationRequest = $request->getOperation();

        // don't check return value, ignore the case when exact the same score variable exists
        // because variables considered as equal only if their's epoch (microtime()) are the same
        $this->getScoreWriter()->store($deliveryExecution, $operationRequest->getScore());

        /** @var  StateServiceInterface $stateService */
        $stateService = $this->getServiceLocator()->get(StateServiceInterface::SERVICE_ID);
        $stateService->finish($deliveryExecution);

        /** @var EventManager $eventManager*/
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        /** @noinspection PhpUnhandledExceptionInspection */
        $eventManager->trigger(new LisScoreReceivedEvent($deliveryExecution->getIdentifier()));

        $this->logInfo(
            sprintf(
                "Score '%s' added for delivery execution '%s'",
                $operationRequest->getScore(),
                $operationRequest->getSourcedId()
            )
        );

        return new ReplaceResponse(
            ReplaceResponse::STATUS_SUCCESS,
            sprintf('Score for %s is now %s', $operationRequest->getSourcedId(), $operationRequest->getScore()),
            ReplaceResponse::CODE_MAJOR_SUCCESS,
            null,
            $request->getMessageIdentifier(),
            $request->getOperationName()
        );
    }

    private function getScoreWriter(): ScoreWriterService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(ScoreWriterService::class);
    }

    private function getDeliveryExecutionGetter(): DeliveryExecutionGetterInterface
    {
        return $this->getServiceLocator()->get(DeliveryExecutionGetterInterface::SERVICE_ID);
    }
}

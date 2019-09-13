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
 */

namespace oat\taoLtiConsumer\controller;

use common_exception_BadRequest;
use common_exception_Error;
use common_exception_InvalidArgumentType;
use GuzzleHttp\Psr7\Response;
use oat\oatbox\event\EventManager;
use oat\taoLtiConsumer\model\classes\ResultService as LtiResultService;
use oat\taoResultServer\models\classes\ResultServerService;
use oat\taoLtiConsumer\model\ResultException;
use oat\taoResultServer\models\Exceptions\DuplicateVariableException;
use Psr\Http\Message\ServerRequestInterface;

class ResultController extends \tao_actions_CommonModule
{
    private $resultService;

    const LIS_SCORE_RECEIVE_EVENT = 'LisScoreReceivedEvent';
    const DELIVERY_EXECUTION_ID = 'DeliveryExecutionID';

    /**
     * @return Response
     * @throws DuplicateVariableException
     * @throws common_exception_Error
     * @throws common_exception_InvalidArgumentType
     * @throws common_exception_BadRequest
     */
    public function manageResult()
    {
        $payload = $this->getPsrRequest()->getBody()->getContents();

        return $this->storeScore($payload);
    }

    /**
     * Stores a score result in a delivery execution
     * @param $payload Input XML string
     * @return Response
     * @throws common_exception_Error
     * @throws common_exception_InvalidArgumentType
     * @throws DuplicateVariableException
     */
    public function storeScore($payload)
    {
        $this->resultService = $this->getServiceLocator()->get(LtiResultService::class);

        try {
            $result = $this->resultService->loadPayload($payload);
        } catch (ResultException $e) {
            return $this->sendResponse($e->getOptionalData(), $e->getCode());
        }

        try {
            $deliveryExecution = $this->resultService->getDeliveryExecution($result);
        } catch (ResultException $e) {
            return $this->sendResponse($e->getOptionalData(), $e->getCode());
        }

        /** @var ResultServerService $resultServerService */
        $resultServerService = $this->getServiceManager()->get(ResultServerService::SERVICE_ID);
        $resultStorageService = $resultServerService->getResultStorage($result['sourcedId']);
        $resultStorageService->storeTestVariable($result['sourcedId'], '', $this->resultService->getScoreVariable($result), '');

        /** @var EventManager $eventManager*/
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
        $eventManager->trigger(self::LIS_SCORE_RECEIVE_EVENT,
            [self::DELIVERY_EXECUTION_ID => $deliveryExecution->getIdentifier()]);

        return $this->sendResponse($this->resultService->getSuccessResult($result), LtiResultService::STATUS_SUCCESS);
    }

    /**
     * @param $params [paramName => value]
     * @param $statusCode int
     * @return Response
     */
    private function sendResponse($params, $statusCode)
    {
        $responseXml = str_replace(array_keys($params), array_values($params), LtiResultService::RESPONSE_TEMPLATE);
        $response = new Response($statusCode, [], $responseXml);
        $this->setResponse($response);
        return $response;
    }
}

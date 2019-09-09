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

use GuzzleHttp\Psr7\Response;
use oat\oatbox\action\Action;
use oat\oatbox\log\LoggerAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use common_exception_BadRequest;
use common_report_Report as Report;
use oat\taoResultServer\models\classes\ResultService;
use oat\taoLtiConsumer\model\classes\ResultService as LtiResultService;

class ResultController extends \tao_actions_CommonModule
{
    use ServiceLocatorAwareTrait;

    private $resultService;

    public function __construct(LtiResultService $resultService)
    {
        $this->resultService = $resultService;
    }

    public function actionResultScore()
    {
        $payload = $this->getRequestParameter('payload');
        $this->manageResult($payload);
    }

    /**
     * Create a list or a list element
     * @return Response
     * @throws common_exception_BadRequest
     */
    public function manageResult($payload)
    {
        if (!$this->isXmlHttpRequest()) {
            // throw new common_exception_BadRequest('wrong request mode');
        }

        $result = $this->resultService->loadPayload($payload);

        if ($result !== null) {
            return $this->sendResponse($result, 501);
        }

        $result = $this->resultService->getResult();

        if (!$this->resultService->isScoreValid($result['score'])) {
//            $this->logError('Score is not in the range [0..1]');
            return $this->sendResponse([
                '{{codeMajor}}' => LtiResultService::FAILURE_MESSAGE,
                '{{description}}' => LtiResultService::$statuses[400],
                '{{messageId}}' => $result['messageIdentifier'],
            ], 400);
        }

        try {
            $resultService = $this->getServiceManager()->get(ResultService::SERVICE_ID);
            $deliveryExecution = $resultService->getDeliveryExecutionById($result['sourcedId']);
        } catch (\Exception $e) {
//            $this->logError('Delivery Execution with ID ' . $sourcedId);
            return $this->sendResponse([
                '{{codeMajor}}' => LtiResultService::FAILURE_MESSAGE,
                '{{description}}' => LtiResultService::$statuses[404],
                '{{messageId}}' => $result['messageIdentifier'],
            ], 404);
        }
    }

    /**
     * @param $params
     * [paramName => value]
     * @return Response
     */
    private function sendResponse($params, $statusCode)
    {
        $responseXml = str_replace(array_keys($params), array_values($params), LtiResultService::RESPONSE_TEMPLATE);
        $response = new Response($statusCode, [], $responseXml);
        return $response;
    }
}

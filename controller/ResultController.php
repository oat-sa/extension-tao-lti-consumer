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

class ResultController extends \tao_actions_CommonModule
{
    use ServiceLocatorAwareTrait;
//    use LoggerAwareTrait;

    const FAILURE_MESSAGE = 'failure';
    const SUCCESS_MESSAGE = 'success';
    const RESPONSE_TEMPLATE = '<?xml version="1.0" encoding="UTF-8"?>
        <imsx_POXEnvelopeResponse xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
            <imsx_POXHeader>
                <imsx_POXResponseHeaderInfo>
                    <imsx_version>V1.0</imsx_version>
                    <imsx_messageIdentifier>{{messageId}}</imsx_messageIdentifier>
                    <imsx_statusInfo>
                        <imsx_codeMajor>{{codeMajor}}</imsx_codeMajor>
                        <imsx_severity>status</imsx_severity>
                        <imsx_description>{{description}}</imsx_description>
                        <imsx_messageRefIdentifier>{{messageIdentifier}}</imsx_messageRefIdentifier>
                        <imsx_operationRefIdentifier>replaceResult</imsx_operationRefIdentifier>
                    </imsx_statusInfo>
                </imsx_POXResponseHeaderInfo>
            </imsx_POXHeader>
            <imsx_POXBody>
                <replaceResultResponse />
            </imsx_POXBody>
        </imsx_POXEnvelopeResponse>
    ';

    private $dom;

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

        $scoreDescriptionTemplate = 'Score for {sourceId} is now {score}';

        $statuses = array(
            400 => 'Invalid score',
            404 => 'DeliveryExecution not found',
            501 => 'Method not implemented (for other requests than replaceResultRequest)',
            500 => 'Other internal error => client should retry',
        );

        try {
            $this->dom = new \DOMDocument();
            $this->dom->loadXML($payload);
            $this->validateResultRequest($payload);
        } catch (\Exception $e) {
//            $this->logError('Request XML does not have replaceResultRequest element');
            return $this->sendResponse([
                '{{codeMajor}}' => self::FAILURE_MESSAGE,
                '{{description}}' =>$statuses[501],
                '{{messageId}}' => '',
            ], 501);
        }

        $result = $this->getResult();

        if (!$this->isScoreValid($result['score'])) {
//            $this->logError('Score is not in the range [0..1]');
            return $this->sendResponse([
                '{{codeMajor}}' => self::FAILURE_MESSAGE,
                '{{description}}' => $statuses[400],
                '{{messageId}}' => $result['messageIdentifier'],
            ], 400);
        }

        try {
            $resultService = $this->getServiceManager()->get(ResultService::SERVICE_ID);
            $deliveryExecution = $resultService->getDeliveryExecutionById($result['sourcedId']);
        } catch (\Exception $e) {
//            $this->logError('Delivery Execution with ID ' . $sourcedId);
            return $this->sendResponse([
                '{{codeMajor}}' => self::FAILURE_MESSAGE,
                '{{description}}' => $statuses[404],
                '{{messageId}}' => $result['messageIdentifier'],
            ], 404);
        }
    }

    private function validateResultRequest()
    {
        $xpath = new \DOMXPath($this->dom);
        $xpath->registerNamespace('lti', "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0");
        $elements = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/lti:replaceResultRequest');

        if ($elements->length === 0) {
            throw new \Exception('Request XML does not have replaceResultRequest element');
        }
    }

    private function isScoreValid($score)
    {
        return (is_numeric($score) && $score >= 0 && $score <= 1);
    }

    private function getResult()
    {
        $xpath = new \DOMXPath($this->dom);
        $xpath->registerNamespace('lti', "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0");

        $messageIdentifier = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXHeader/lti:imsx_POXRequestHeaderInfo/lti:imsx_messageIdentifier')
            ->item(0)->nodeValue;
        $score = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/lti:replaceResultRequest/lti:resultRecord/lti:result/lti:resultScore/lti:textString')
            ->item(0)->nodeValue;
        $sourcedId = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/lti:replaceResultRequest/lti:resultRecord/lti:sourcedGUID/lti:sourcedId')
            ->item(0)->nodeValue;

        return [
            'messageIdentifier' => $messageIdentifier,
            'score' => $score,
            'sourcedId' => $sourcedId,
        ];
    }

    /**
     * @param $params
     * [paramName => value]
     * @return Response
     */
    private function sendResponse($params, $statusCode)
    {
        $responseXml = str_replace(array_keys($params), array_values($params), self::RESPONSE_TEMPLATE);
        $response = new Response($statusCode, [], $responseXml);
        return $response;
    }
}

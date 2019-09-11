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

namespace oat\taoLtiConsumer\model\classes;

use oat\oatbox\service\ServiceManagerAwareTrait;
use oat\oatbox\log\LoggerAwareTrait;
use oat\taoDelivery\model\execution\ServiceProxy;
use taoResultServer_models_classes_OutcomeVariable as OutcomeVariable;

/**
 * ResultService class to manage XML result data
 */
class ResultService
{
    use ServiceManagerAwareTrait;
    use LoggerAwareTrait;

    const SERVICE_ID = 'result_service';
    const FAILURE_MESSAGE = 'failure';
    const SUCCESS_MESSAGE = 'success';

    const TEMPLATE_VAR_CODE_MAJOR = '{{codeMajor}}';
    const TEMPLATE_VAR_DESCRIPTION = '{{description}}';
    const TEMPLATE_VAR_MESSAGE_ID = '{{messageId}}';
    const TEMPLATE_VAR_MESSAGE_REF_IDENTIFIER = '{{messageIdentifier}}';
    const RESPONSE_TEMPLATE = '<?xml version="1.0" encoding="UTF-8"?>
        <imsx_POXEnvelopeResponse xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
            <imsx_POXHeader>
                <imsx_POXResponseHeaderInfo>
                    <imsx_version>V1.0</imsx_version>
                    <imsx_messageIdentifier>' . self::TEMPLATE_VAR_MESSAGE_ID . '</imsx_messageIdentifier>
                    <imsx_statusInfo>
                        <imsx_codeMajor>' . self::TEMPLATE_VAR_CODE_MAJOR . '</imsx_codeMajor>
                        <imsx_severity>status</imsx_severity>
                        <imsx_description>' . self::TEMPLATE_VAR_DESCRIPTION . '</imsx_description>
                        <imsx_messageRefIdentifier>' . self::TEMPLATE_VAR_MESSAGE_REF_IDENTIFIER . '</imsx_messageRefIdentifier>
                        <imsx_operationRefIdentifier>replaceResult</imsx_operationRefIdentifier>
                    </imsx_statusInfo>
                </imsx_POXResponseHeaderInfo>
            </imsx_POXHeader>
            <imsx_POXBody>
                <replaceResultResponse />
            </imsx_POXBody>
        </imsx_POXEnvelopeResponse>
    ';
    const SCORE_DESCRIPTION_TEMPLATE = 'Score for {{sourceId}} is now {{score}}';

    const STATUS_INVALID_SCORE = 400;
    const STATUS_DELIVERY_EXECUTION_NOT_FOUND = 404;
    const STATUS_METHOD_NOT_IMPLEMENTED = 501;
    const STATUS_INTERNAL_SERVER_ERROR = 500;
    const STATUS_SUCCESS = 201;

    public static $statuses = array(
        self::STATUS_INVALID_SCORE => 'Invalid score',
        self::STATUS_DELIVERY_EXECUTION_NOT_FOUND => 'DeliveryExecution not found',
        self::STATUS_METHOD_NOT_IMPLEMENTED => 'Method not implemented',
        self::STATUS_INTERNAL_SERVER_ERROR => 'Internal server error, please retry',
    );

    /**
     * @param $payload string
     * @return array [array $result, int $status]
     */
    public function loadPayload($payload)
    {
        try {
            $dom = new \DOMDocument();
            $dom->loadXML($payload);
            $this->validateResultRequest($dom);
            return $this->getResult($dom);
        } catch (\Exception $e) {
            // $this->logError('Request XML does not have replaceResultRequest element');
            return [[
                self::TEMPLATE_VAR_CODE_MAJOR => self::FAILURE_MESSAGE,
                self::TEMPLATE_VAR_DESCRIPTION => self::$statuses[501],
                self::TEMPLATE_VAR_MESSAGE_ID => '',
                self::TEMPLATE_VAR_MESSAGE_REF_IDENTIFIER => '',
            ], self::STATUS_METHOD_NOT_IMPLEMENTED];
        }
    }

    /**
     * @param \DOMDocument $dom
     * @return array [array $result, int $status]
     */
    public function getResult($dom)
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('lti', "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0");

        $messageIdentifier = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXHeader/lti:imsx_POXRequestHeaderInfo/lti:imsx_messageIdentifier')
            ->item(0)->nodeValue;
        $score = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/lti:replaceResultRequest/lti:resultRecord/lti:result/lti:resultScore/lti:textString')
            ->item(0)->nodeValue;
        $sourcedId = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/lti:replaceResultRequest/lti:resultRecord/lti:sourcedGUID/lti:sourcedId')
            ->item(0)->nodeValue;

        if (!$this->isScoreValid($score)) {
            return [[
                self::TEMPLATE_VAR_CODE_MAJOR => self::FAILURE_MESSAGE,
                self::TEMPLATE_VAR_DESCRIPTION => self::$statuses[400],
                self::TEMPLATE_VAR_MESSAGE_ID => $messageIdentifier,
            ], self::STATUS_INVALID_SCORE];
        }

        return [[
            'messageIdentifier' => $messageIdentifier,
            'score' => $score,
            'sourcedId' => $sourcedId,
        ], self::STATUS_SUCCESS];
    }

    /**
     * @param array $result
     * @return array [array|object $result, int $status]
     */
    public function getDeliveryExecution($result)
    {
        try {
            /** @var ServiceProxy $resultService */
            $resultService = $this->getServiceManager()->get(ServiceProxy::SERVICE_ID);
            $deliveryExecution = $resultService->getDeliveryExecution($result['sourcedId']);
        } catch (\Exception $e) {
            // $this->logError('Delivery Execution with ID ' . $sourcedId);
            return [[
                self::TEMPLATE_VAR_CODE_MAJOR => self::FAILURE_MESSAGE,
                self::TEMPLATE_VAR_DESCRIPTION => self::$statuses[404],
                self::TEMPLATE_VAR_MESSAGE_ID => $result['messageIdentifier'],
            ], self::STATUS_DELIVERY_EXECUTION_NOT_FOUND];
        }

        return [$deliveryExecution, self::STATUS_SUCCESS];
    }

    /**
     * @param array $result
     * @return array
     */
    public function getSuccessResult($result)
    {
        return [
            self::TEMPLATE_VAR_CODE_MAJOR => self::SUCCESS_MESSAGE,
            self::TEMPLATE_VAR_DESCRIPTION => str_replace(
                ['{{sourceId}}', '{{score}}'],
                [$result['sourcedId'], $result['score']],
                self::SCORE_DESCRIPTION_TEMPLATE),
            self::TEMPLATE_VAR_MESSAGE_ID => $result['messageIdentifier'],
            self::TEMPLATE_VAR_MESSAGE_REF_IDENTIFIER => $result['sourcedId'],
        ];
    }

    /**
     * @param array $result
     * @return OutcomeVariable
     * @throws \common_exception_InvalidArgumentType
     */
    public function getScoreVariable($result)
    {
        $scoreVariable = new OutcomeVariable();
        $scoreVariable->setIdentifier('score');
        $scoreVariable->setCardinality(OutcomeVariable::CARDINALITY_SINGLE);
        $scoreVariable->setBaseType('float');
        $scoreVariable->setEpoch(microtime());
        $scoreVariable->setValue($result['score']);

        return $scoreVariable;
    }

    /**
     * @param mixed $score
     * @return bool
     */
    public function isScoreValid($score)
    {
        return (is_numeric($score) && $score >= 0 && $score <= 1);
    }

    /**
     * @param \DOMDocument $dom
     * @throws \Exception
     */
    private function validateResultRequest($dom)
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('lti', "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0");
        $elements = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/lti:replaceResultRequest');

        if ($elements->length === 0) {
            throw new \Exception('Request XML does not have replaceResultRequest element');
        }
    }
}
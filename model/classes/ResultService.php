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

use oat\taoDelivery\model\container\delivery\AbstractContainer;
use oat\taoDelivery\model\container\execution\ExecutionClientContainer;
use oat\taoDelivery\model\container\ExecutionContainer;
use oat\taoDelivery\model\execution\DeliveryExecution;
use IMSGlobal\LTI\ToolProvider\ToolConsumer;
use oat\oatbox\session\SessionService;
use oat\generis\model\OntologyAwareTrait;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;

use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Class LtiDeliveryContainer
 *
 * A delivery container to manage LTI based delivery
 */
class ResultService
{
    use ServiceLocatorAwareTrait;
    //    use LoggerAwareTrait;

    const SERVICE_ID = 'result_service';
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
    const SCORE_DESCRIPTION_TEMPLATE = 'Score for {{sourceId}} is now {{score}}';

    private $dom;
    public static $statuses = array(
        400 => 'Invalid score',
        404 => 'DeliveryExecution not found',
        501 => 'Method not implemented (for other requests than replaceResultRequest)',
        500 => 'Other internal error => client should retry',
    );

    /**
     * @param $payload string
     * @return array|null
     */
    public function loadPayload($payload)
    {
        try {
            $this->dom = new \DOMDocument();
            $this->dom->loadXML($payload);
            $this->validateResultRequest($payload);
        } catch (\Exception $e) {
//            $this->logError('Request XML does not have replaceResultRequest element');
            return [
                '{{codeMajor}}' => self::FAILURE_MESSAGE,
                '{{description}}' => self::$statuses[501],
                '{{messageId}}' => '',
            ];
        }
    }

    public function getResult()
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

    public function isScoreValid($score)
    {
        return (is_numeric($score) && $score >= 0 && $score <= 1);
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
}
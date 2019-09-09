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

use oat\oatbox\action\Action;
use oat\oatbox\log\LoggerAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use common_exception_BadRequest;
use common_report_Report as Report;
use oat\taoResultServer\models\classes\ResultService;

class ResultController extends \tao_actions_RestController implements Action
{
    use ServiceLocatorAwareTrait;
    use LoggerAwareTrait;

    const FAILURE_MESSAGE = 'failure';
    const SUCCESS_MESSAGE = 'success';

    /**
     * Create a list or a list element
     * @return Report
     * @throws common_exception_BadRequest
     */
    public function __invoke($params)
    {
        if (!$this->isXmlHttpRequest()) {
            // throw new common_exception_BadRequest('wrong request mode');
        }

        $scoreDescriptionTemplate = 'Score for {sourceId} is now {score}';
        $responseTemplate = '<?xml version="1.0" encoding="UTF-8"?>
            <imsx_POXEnvelopeResponse xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
                <imsx_POXHeader>
                    <imsx_POXResponseHeaderInfo>
                        <imsx_version>V1.0</imsx_version>
                        <imsx_messageIdentifier>{messageId}</imsx_messageIdentifier>
                        <imsx_statusInfo>
                            <imsx_codeMajor>{codeMajor}</imsx_codeMajor>
                            <imsx_severity>status</imsx_severity>
                            <imsx_description>{description}</imsx_description>
                            <imsx_messageRefIdentifier>{messageIdentifier}</imsx_messageRefIdentifier>
                            <imsx_operationRefIdentifier>replaceResult</imsx_operationRefIdentifier>
                        </imsx_statusInfo>
                    </imsx_POXResponseHeaderInfo>
                </imsx_POXHeader>
                <imsx_POXBody>
                    <replaceResultResponse />
                </imsx_POXBody>
            </imsx_POXEnvelopeResponse>
        ';
        $statuses = array(
            400 => 'Invalid score',
            404 => 'DeliveryExecution not found',
            501 => 'Method not implemented (for other requests than replaceResultRequest)',
            500 => 'Other internal error => client should retry',
        );

        $doc = new \DOMDocument();
//        $doc->loadXML($this->getRequestParameter('source'));
        $doc->loadXML('<?xml version="1.0" encoding="UTF-8"?>
            <imsx_POXEnvelopeRequest xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
                <imsx_POXHeader>
                    <imsx_POXRequestHeaderInfo>
                        <imsx_version>V1.0</imsx_version>
                        <imsx_messageIdentifier>999999123</imsx_messageIdentifier>
                    </imsx_POXRequestHeaderInfo>
                </imsx_POXHeader>
                <imsx_POXBody>
                    <replaceResultRequest>
                        <resultRecord>
                            <sourcedGUID>
                                <sourcedId>3124567</sourcedId>
                            </sourcedGUID>
                            <result>
                                <resultScore>
                                    <language>en</language>
                                    <textString>0.92</textString>
                                </resultScore>
                            </result>
                        </resultRecord>
                    </replaceResultRequest>
                </imsx_POXBody>
            </imsx_POXEnvelopeRequest>
        ');
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('lti', "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0");
        $elements = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/lti:replaceResultRequest');
        $messageIdentifier = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXHeader/lti:imsx_POXRequestHeaderInfo/lti:imsx_messageIdentifier')
            ->item(0)->nodeValue;

        if ($elements->length === 0) {
            $this->logError('Request XML does not have replaceResultRequest element');
            $xmlResponse = str_replace('{codeMajor}', self::FAILURE_MESSAGE, $responseTemplate);
            $xmlResponse = str_replace('{description}', $statuses[501], $xmlResponse);
            $xmlResponse = str_replace('{messageId}', $messageIdentifier, $xmlResponse);
            return $xmlResponse;
        }

        $score = (float)$xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/lti:replaceResultRequest/lti:resultRecord/lti:result/lti:resultScore/lti:textString')
            ->item(0)->nodeValue;
        $sourcedId = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/lti:replaceResultRequest/lti:resultRecord/lti:sourcedGUID/lti:sourcedId')
            ->item(0)->nodeValue;

        if ($score < 0 || $score > 1) {
            $this->logError('Score is not in the range [0..1]');
            $xmlResponse = str_replace('{codeMajor}', self::FAILURE_MESSAGE, $responseTemplate);
            $xmlResponse = str_replace('{description}', $statuses[400], $xmlResponse);
            $xmlResponse = str_replace('{messageId}', $messageIdentifier, $xmlResponse);
            return $xmlResponse;
        }

        $resultService = $this->getServiceManager()->get(ResultService::SERVICE_ID);
        $deliveryExecution = $resultService->getDeliveryExecutionById($sourcedId);

        var_dump($deliveryExecution);

        var_dump($score);
        var_dump($sourcedId);
//        var_dump($elements);
//        var_dump($elements->item(0));
    }
}

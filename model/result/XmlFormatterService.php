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

use oat\oatbox\service\ConfigurableService;
use oat\taoLtiConsumer\model\classes\ResultService as LtiResultService;

/**
 * Class LtiXmlFormatterService
 * Class to manage XML result data with score and to store it in DeliveryExecution
 * @package oat\taoLtiConsumer\model\classes
 */
class XmlFormatterService extends ConfigurableService
{
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

    /**
     * @param $params [paramName => value]
     * @return string
     **/
    public function getXmlResponse(array $params)
    {
        $responseXml = str_replace(array_keys($params), array_values($params), self::RESPONSE_TEMPLATE);

        return $responseXml;
    }
}

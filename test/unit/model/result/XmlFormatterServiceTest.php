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

namespace oat\taoLtiConsumer\test\unit\model\result\parser;

use oat\generis\test\TestCase;
use oat\taoLtiConsumer\model\result\XmlFormatterService;

class XmlFormatterServiceTest extends TestCase
{
    const EXPECTED_RESPONSE = '<?xml version="1.0" encoding="UTF-8"?>
        <imsx_POXEnvelopeResponse xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
            <imsx_POXHeader>
                <imsx_POXResponseHeaderInfo>
                    <imsx_version>V1.0</imsx_version>
                    <imsx_messageIdentifier>0</imsx_messageIdentifier>
                    <imsx_statusInfo>
                        <imsx_codeMajor>0</imsx_codeMajor>
                        <imsx_severity>status</imsx_severity>
                        <imsx_description>0</imsx_description>
                        <imsx_messageRefIdentifier>0</imsx_messageRefIdentifier>
                        <imsx_operationRefIdentifier>replaceResult</imsx_operationRefIdentifier>
                    </imsx_statusInfo>
                </imsx_POXResponseHeaderInfo>
            </imsx_POXHeader>
            <imsx_POXBody>
                <replaceResultResponse />
            </imsx_POXBody>
        </imsx_POXEnvelopeResponse>
    ';

    public function testGetXmlResponse()
    {
        $subject = new XmlFormatterService();
        $result = $subject->getXmlResponse([
            XmlFormatterService::TEMPLATE_VAR_MESSAGE_REF_IDENTIFIER => '0',
            XmlFormatterService::TEMPLATE_VAR_MESSAGE_ID => '0',
            XmlFormatterService::TEMPLATE_VAR_DESCRIPTION => '0',
            XmlFormatterService::TEMPLATE_VAR_CODE_MAJOR => '0',
        ]);
        $this->assertEquals(self::EXPECTED_RESPONSE, $result);
    }
}

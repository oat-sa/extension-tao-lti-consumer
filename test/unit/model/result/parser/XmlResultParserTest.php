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
use oat\taoLtiConsumer\model\result\parser\dataExtractor\ReplaceResultDataExtractor;
use oat\taoLtiConsumer\model\result\parser\XmlResultParser;

class XmlResultParserTest extends TestCase
{
    public function testParseReplaceResult()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
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
                                        <textString>0.69</textString>
                                    </resultScore>
                                </result>
                            </resultRecord>
                        </replaceResultRequest>
                    </imsx_POXBody>
                </imsx_POXEnvelopeRequest>';

        $parser = new XmlResultParser();
        $parser->parse($xml);
        $this->assertEquals(ReplaceResultDataExtractor::REQUEST_TYPE, $parser->getRequestType());
        var_dump($parser->getData());
    }

}
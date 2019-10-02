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
 * Copyright (c) 2019 (update and modification) Open Assessment Technologies SA
 */

namespace oat\taoLtiConsumer\test\unit\model\result\parser\dataExtractor;

use DOMDocument;
use DOMXPath;
use oat\generis\test\TestCase;
use oat\taoLtiConsumer\model\result\parser\dataExtractor\ReplaceResultDataExtractorInterface;
use oat\taoLtiConsumer\model\result\ResultException;

class ReplaceResultDataExtractorTest extends TestCase
{
    /**
     * @return ReplaceResultDataExtractorInterface
     */
    public function testAccept()
    {
        $xpath =  $this->getXpath('<?xml version="1.0" encoding="UTF-8"?>
                <imsx_POXEnvelopeRequest xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
                    <imsx_POXHeader>
                        <imsx_POXRequestHeaderInfo>
                            <imsx_version>V1.0</imsx_version>
                            <imsx_messageIdentifier>{{messageIdentifier}}</imsx_messageIdentifier>
                        </imsx_POXRequestHeaderInfo>
                    </imsx_POXHeader>
                    <imsx_POXBody>
                        <replaceResultRequest>
                            <resultRecord>
                                <sourcedGUID>
                                    <sourcedId>{{sourcedid}}</sourcedId>
                                </sourcedGUID>
                                <result>
                                    <resultScore>
                                        <language>en</language>
                                        <textString>{{score}}</textString>
                                    </resultScore>
                                </result>
                            </resultRecord>
                        </replaceResultRequest>
                    </imsx_POXBody>
                </imsx_POXEnvelopeRequest>');

        $service = new ReplaceResultDataExtractorInterface();
        $this->assertTrue($service->accepts($xpath));

        return $service;
    }

    public function testDoNotAcceptNoReplace()
    {
        $xpath =  $this->getXpath('<?xml version="1.0" encoding="UTF-8"?>
                <imsx_POXEnvelopeRequest xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
                    <imsx_POXHeader>
                        <imsx_POXRequestHeaderInfo>
                            <imsx_version>V1.0</imsx_version>
                            <imsx_messageIdentifier>{{messageIdentifier}}</imsx_messageIdentifier>
                        </imsx_POXRequestHeaderInfo>
                    </imsx_POXHeader>
                    <imsx_POXBody>
                        <deleteResultRequest>
                            <resultRecord>
                                <sourcedGUID>
                                    <sourcedId>{{sourcedid}}</sourcedId>
                                </sourcedGUID>
                                <result>
                                    <resultScore>
                                        <language>en</language>
                                        <textString>{{score}}</textString>
                                    </resultScore>
                                </result>
                            </resultRecord>
                        </deleteResultRequest>
                    </imsx_POXBody>
                </imsx_POXEnvelopeRequest>');

        $service = new ReplaceResultDataExtractorInterface();
        $this->assertFalse($service->accepts($xpath));
    }

    public function testDoNotAcceptMultiBody()
    {
        $xpath =  $this->getXpath('<?xml version="1.0" encoding="UTF-8"?>
                <imsx_POXEnvelopeRequest xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
                    <imsx_POXHeader>
                        <imsx_POXRequestHeaderInfo>
                            <imsx_version>V1.0</imsx_version>
                            <imsx_messageIdentifier>{{messageIdentifier}}</imsx_messageIdentifier>
                        </imsx_POXRequestHeaderInfo>
                    </imsx_POXHeader>
                    <imsx_POXBody>
                        <replaceResultRequest>
                            <resultRecord>
                                <sourcedGUID>
                                    <sourcedId>{{sourcedid}}</sourcedId>
                                </sourcedGUID>
                                <result>
                                    <resultScore>
                                        <language>en</language>
                                        <textString>{{score}}</textString>
                                    </resultScore>
                                </result>
                            </resultRecord>
                        </replaceResultRequest>
                        <replaceResultRequest>
                            <resultRecord>
                                <sourcedGUID>
                                    <sourcedId>{{sourcedid}}</sourcedId>
                                </sourcedGUID>
                                <result>
                                    <resultScore>
                                        <language>en</language>
                                        <textString>{{score}}</textString>
                                    </resultScore>
                                </result>
                            </resultRecord>
                        </replaceResultRequest>
                    </imsx_POXBody>
                </imsx_POXEnvelopeRequest>');

        $service = new ReplaceResultDataExtractorInterface();
        $this->assertFalse($service->accepts($xpath));
    }

    public function testGetRequestType()
    {
        $this->assertSame(ReplaceResultDataExtractorInterface::REQUEST_TYPE, (new ReplaceResultDataExtractorInterface())->getRequestType());
    }

    /**
     * @depends testAccept
     * @param ReplaceResultDataExtractorInterface $service
     * @throws ResultException
     */
    public function testGetData(ReplaceResultDataExtractorInterface $service)
    {
        $xpath =  $this->getXpath('<?xml version="1.0" encoding="UTF-8"?>
                <imsx_POXEnvelopeRequest xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
                    <imsx_POXHeader>
                        <imsx_POXRequestHeaderInfo>
                            <imsx_version>V1.0</imsx_version>
                            <imsx_messageIdentifier>123</imsx_messageIdentifier>
                        </imsx_POXRequestHeaderInfo>
                    </imsx_POXHeader>
                    <imsx_POXBody>
                        <replaceResultRequest>
                            <resultRecord>
                                <sourcedGUID>
                                    <sourcedId>456</sourcedId>
                                </sourcedGUID>
                                <result>
                                    <resultScore>
                                        <language>en</language>
                                        <textString>789</textString>
                                    </resultScore>
                                </result>
                            </resultRecord>
                        </replaceResultRequest>
                    </imsx_POXBody>
                </imsx_POXEnvelopeRequest>');

        $data = $service->getData($xpath);
        $this->assertArrayHasKey('messageIdentifier', $data);
        $this->assertEquals("123", $data['messageIdentifier']);
        $this->assertArrayHasKey('sourcedId', $data);
        $this->assertEquals("456", $data['sourcedId']);
        $this->assertArrayHasKey('score', $data);
        $this->assertEquals("789", $data['score']);
    }

    /**
     * @dataProvider getDataWithInvalidDataProvider
     * @param DOMXPath $xpath
     * @throws ResultException
     */
    public function testGetDataWithInvalidData(DOMXPath $xpath)
    {
        $service = $this->testAccept();

        $this->expectException(ResultException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Internal server error, please retry');

        $service->getData($xpath);
    }

    public function getDataWithInvalidDataProvider()
    {
        return [
            // Invalid message identifier
            [
                $this->getXpath('<?xml version="1.0" encoding="UTF-8"?>
                <imsx_POXEnvelopeRequest xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
                    <imsx_POXBody>
                        <replaceResultRequest>
                            <resultRecord>
                                <sourcedGUID>
                                    <sourcedId>{{sourcedid}}</sourcedId>
                                </sourcedGUID>
                                <result>
                                    <resultScore>
                                        <language>en</language>
                                        <textString>{{score}}</textString>
                                    </resultScore>
                                </result>
                            </resultRecord>
                        </replaceResultRequest>
                    </imsx_POXBody>
                </imsx_POXEnvelopeRequest>')
            ],

            // Invalid sourcedId
            [
                $this->getXpath('<?xml version="1.0" encoding="UTF-8"?>
                <imsx_POXEnvelopeRequest xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
                    <imsx_POXHeader>
                        <imsx_POXRequestHeaderInfo>
                            <imsx_version>V1.0</imsx_version>
                            <imsx_messageIdentifier>{{messageIdentifier}}</imsx_messageIdentifier>
                        </imsx_POXRequestHeaderInfo>
                    </imsx_POXHeader>
                    <imsx_POXBody>
                        <replaceResultRequest>
                            <resultRecord>
                                <result>
                                    <resultScore>
                                        <language>en</language>
                                        <textString>{{score}}</textString>
                                    </resultScore>
                                </result>
                            </resultRecord>
                        </replaceResultRequest>
                    </imsx_POXBody>
                </imsx_POXEnvelopeRequest>')
            ],

            // Invalid score
            [
                $xpath =  $this->getXpath('<?xml version="1.0" encoding="UTF-8"?>
                <imsx_POXEnvelopeRequest xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
                    <imsx_POXHeader>
                        <imsx_POXRequestHeaderInfo>
                            <imsx_version>V1.0</imsx_version>
                            <imsx_messageIdentifier>{{messageIdentifier}}</imsx_messageIdentifier>
                        </imsx_POXRequestHeaderInfo>
                    </imsx_POXHeader>
                    <imsx_POXBody>
                        <replaceResultRequest>
                            <resultRecord>
                                <sourcedGUID>
                                    <sourcedId>{{sourcedid}}</sourcedId>
                                </sourcedGUID>
                            </resultRecord>
                        </replaceResultRequest>
                    </imsx_POXBody>
                </imsx_POXEnvelopeRequest>')
            ]
        ];
    }

    /**
     * Load xml string to Xpath object
     *
     * @param $xml
     * @return DOMXPath
     */
    protected function getXpath($xml)
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('lti', "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0");
        return $xpath;
    }

}
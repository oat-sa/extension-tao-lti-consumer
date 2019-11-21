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

namespace oat\taoLtiConsumer\test\unit\model\result\messages;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseInterface;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseSerializer;
use oat\taoLtiConsumer\model\result\operations\BasicResponse;
use oat\taoLtiConsumer\model\result\operations\replace\Response;
use SimpleXMLElement;

class LisOutcomeResponseSerializerTest extends TestCase
{
    public function testCreateXmlElementWithoutBodyResponseNode()
    {
        /** @var MockObject|BasicResponse $response */
        $response = $this->createMock(LisOutcomeResponseInterface::class);
        $response->method('getMessageIdentifier')->willReturn('msg_id');
        $response->method('getCodeMajor')->willReturn('unsupported');
        $response->method('getStatusDescription')->willReturn('with_special_char_<a>"');
        $response->method('getMessageRefIdentifier')->willReturn('m_ref_id');
        $response->method('getOperationRefIdentifier')->willReturn('wrongOperationRequest');

        $serializer = new LisOutcomeResponseSerializer();
        $xml = $serializer->createXmlElement($response)->asXML();

        $dom = new DOMDocument();
        $this->assertTrue($dom->loadXML($xml));

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ns', 'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0');

        // Use this hack to add extra node to the body element because
        // XSD schema requires it despite the description of unsupported response at
        // https://www.imsglobal.org/specs/ltiomv1p0/specification
        $this->addElementToBody($xpath, 'replaceResultResponse');

        $this->assertTrue($dom->schemaValidate(
            __DIR__ . '../../../../../../doc/ltiXsd/OMSv1p0_LTIv1p1Profile_SyncXSD_v1p0.xsd'
        ));

        $headerInfoPath = '/ns:imsx_POXEnvelopeResponse/ns:imsx_POXHeader/ns:imsx_POXResponseHeaderInfo';
        $this->assertEquals('msg_id', $xpath->evaluate($headerInfoPath . '/ns:imsx_messageIdentifier')[0]->nodeValue);
        $this->assertEquals(
            'unsupported',
            $xpath->evaluate($headerInfoPath . '/ns:imsx_statusInfo/ns:imsx_codeMajor')[0]->nodeValue
        );
        $this->assertEquals(
            'm_ref_id',
            $xpath->evaluate($headerInfoPath . '/ns:imsx_statusInfo/ns:imsx_messageRefIdentifier')[0]->nodeValue
        );
        $this->assertEquals(
            'wrongOperationRequest',
            $xpath->evaluate($headerInfoPath . '/ns:imsx_statusInfo/ns:imsx_operationRefIdentifier')[0]->nodeValue
        );
        $this->assertEquals(
            'with_special_char_<a>"',
            $xpath->evaluate($headerInfoPath . '/ns:imsx_statusInfo/ns:imsx_description')[0]->nodeValue
        );
    }

    public function testCreateXmlElementWithBodyNode()
    {
        /** @var MockObject|Response $response */
        $response = $this->createMock(LisOutcomeResponseInterface::class);
        $response->method('getMessageIdentifier')->willReturn('msg_id');
        $response->method('getCodeMajor')->willReturn('success');
        $response->method('getStatusDescription')->willReturn('with_special_char_<a>"');
        $response->method('getMessageRefIdentifier')->willReturn('m_ref_id');
        $response->method('getOperationRefIdentifier')->willReturn('replaceResultRequest');

        $serializer = new LisOutcomeResponseSerializer();
        $bodyNode = new SimpleXMLElement('<replaceResultResponse />');
        $xml = $serializer->createXmlElement($response, $bodyNode)->asXML();

        $dom = new DOMDocument();
        $this->assertTrue($dom->loadXML($xml));
        $this->assertTrue($dom->schemaValidate(
            __DIR__ . '../../../../../../doc/ltiXsd/OMSv1p0_LTIv1p1Profile_SyncXSD_v1p0.xsd'
        ));

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ns', 'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0');
        $headerInfoPath = '/ns:imsx_POXEnvelopeResponse/ns:imsx_POXHeader/ns:imsx_POXResponseHeaderInfo';
        $this->assertEquals(
            'replaceResultResponse',
            $xpath->evaluate('/ns:imsx_POXEnvelopeResponse/ns:imsx_POXBody/ns:*')[0]->nodeName
        );
        $this->assertEquals('msg_id', $xpath->evaluate($headerInfoPath . '/ns:imsx_messageIdentifier')[0]->nodeValue);
        $this->assertEquals(
            'success',
            $xpath->evaluate($headerInfoPath . '/ns:imsx_statusInfo/ns:imsx_codeMajor')[0]->nodeValue
        );
        $this->assertEquals(
            'm_ref_id',
            $xpath->evaluate($headerInfoPath . '/ns:imsx_statusInfo/ns:imsx_messageRefIdentifier')[0]->nodeValue
        );
        $this->assertEquals(
            'replaceResultRequest',
            $xpath->evaluate($headerInfoPath . '/ns:imsx_statusInfo/ns:imsx_operationRefIdentifier')[0]->nodeValue
        );
        $this->assertEquals(
            'with_special_char_<a>"',
            $xpath->evaluate($headerInfoPath . '/ns:imsx_statusInfo/ns:imsx_description')[0]->nodeValue
        );
    }

    /**
     * @param DOMXPath $xpath
     * @param string $elementName
     */
    protected function addElementToBody(DOMXPath $xpath, $elementName) {
        /** @var DOMNode $body */
        $body = $xpath->evaluate('/ns:imsx_POXEnvelopeResponse/ns:imsx_POXBody')[0];
        $newElement = new DOMElement($elementName, '', 'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0');
        $body->appendChild($newElement);
    }
}

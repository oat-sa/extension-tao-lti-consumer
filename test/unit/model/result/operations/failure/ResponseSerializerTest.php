<?php

use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoLtiConsumer\model\result\operations\failure\Response;
use oat\taoLtiConsumer\model\result\operations\failure\OperationResponseSerializer;
use oat\taoLtiConsumer\model\result\operations\OperationsCollection;

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

class ResponseSerializerTest extends TestCase
{
    public function testSerialize()
    {
        /** @var MockObject|Response $response */
        $response = $this->createMock(Response::class);
        $response->method('getOperationName')->willReturn('replaceResultRequest');
        $response->method('getMessageIdentifier')->willReturn('msg_id');
        $response->method('getCodeMajor')->willReturn('failure');
        $response->method('getStatusDescription')->willReturn('with_special_char_<a>"');
        $response->method('getMessageRefIdentifier')->willReturn('m_ref_id');
        $response->method('getOperationRefIdentifier')->willReturn('replaceResultRequest');

        $operationCollection = $this->createMock(OperationsCollection::class);
        $operationCollection->expects($this->once())
            ->method('getBodyResponseElementName')
            ->with('replaceResultRequest')
            ->willReturn('replaceResultResponse');

        $serializer = new OperationResponseSerializer();
        $serializer->setServiceLocator($this->getServiceLocatorMock([
            OperationsCollection::class => $operationCollection
        ]));
        $xml = $serializer->toXml($response);

        $dom = new DOMDocument();
        $this->assertTrue($dom->loadXML($xml));
        $this->assertTrue($dom->schemaValidate(
            __DIR__ . '../../../../../../../doc/ltiXsd/OMSv1p0_LTIv1p1Profile_SyncXSD_v1p0.xsd'
        ));

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ns', 'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0');
        $headerInfoPath = '/ns:imsx_POXEnvelopeResponse/ns:imsx_POXHeader/ns:imsx_POXResponseHeaderInfo';
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
        $this->assertEquals(
            'replaceResultRequest',
            $xpath->evaluate($headerInfoPath . '/ns:imsx_POXEnvelopeResponse/ns:imsx_statusInfo/ns:imsx_operationRefIdentifier')[0]->nodeValue
        );
    }
}

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

use DOMNode;
use DOMXPath;
use Exception;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequestParser;
use oat\taoLtiConsumer\model\result\operations\OperationRequestInterface;
use oat\taoLtiConsumer\model\result\operations\OperationRequestParserInterface;
use oat\taoLtiConsumer\model\result\operations\OperationsCollection;
use oat\taoLtiConsumer\model\result\ParsingException;

class LisOutcomeRequestParserTest extends TestCase
{
    public function testParseValid()
    {
        $operationRequestMock = $this->createMock(OperationRequestInterface::class);

        /** @var OperationRequestParserInterface|MockObject $operationReqParserMock */
        $operationReqParserMock = $this->createMock(OperationRequestParserInterface::class);
        $operationReqParserMock->expects($this->once())
            ->method('parse')
            ->willReturnCallback(function (DOMXPath $xpath, $nsPrefix, DOMNode $operationNode) use ($operationRequestMock) {
                if ($operationNode->nodeName !== 'replaceResultRequest') {
                    throw new Exception($operationNode->nodeName . ' node passed to operation parser mock');
                }
                if ($xpath->evaluate(sprintf('./%1$s:testInnerNode', $nsPrefix), $operationNode)->length !== 1) {
                    throw new Exception('invalid xpath instance passed to operation parser mock');
                }
                return $operationRequestMock;
            });

        /** @var OperationsCollection|MockObject $opCollectionMock */
        $opCollectionMock = $this->createMock(OperationsCollection::class);
        $opCollectionMock->expects($this->once())
            ->method('getOperationRequestParser')
            ->with('replaceResultRequest')
            ->willReturn($operationReqParserMock);

        $parser = new LisOutcomeRequestParser();
        $parser->setServiceLocator($this->getServiceLocatorMock([
            OperationsCollection::class => $opCollectionMock
        ]));
        /** @noinspection PhpUnhandledExceptionInspection */
        $result = $parser->parse($this->getReplaceRequestXml('msg_id'));
        $this->assertEquals('msg_id', $result->getMessageIdentifier());
        $this->assertEquals('replaceResultRequest', $result->getOperationName());
        $this->assertEquals($operationRequestMock, $result->getOperation());
    }

    public function testParseNoMessageId()
    {
        $parser = new LisOutcomeRequestParser();
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->expectException(ParsingException::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        $parser->parse($this->getReplaceRequestXml(null));
    }

    public function testParseInvalidXml()
    {
        $parser = new LisOutcomeRequestParser();
        $this->expectException(ParsingException::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        $parser->parse('<invalid xml');
    }

    public function testParseWithoutOperationNode()
    {
        $parser = new LisOutcomeRequestParser();
        $this->expectException(ParsingException::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        $parser->parse($this->getRequestXml(null));
    }

    public function testParseUnknownOperation()
    {
        /** @var OperationsCollection|MockObject $opCollectionMock */
        $opCollectionMock = $this->createMock(OperationsCollection::class);
        $opCollectionMock->expects($this->once())
            ->method('getOperationRequestParser')
            ->with('unknownOperation')
            ->willReturn(null);

        $parser = new LisOutcomeRequestParser();
        $parser->setServiceLocator($this->getServiceLocatorMock([
            OperationsCollection::class => $opCollectionMock
        ]));

        /** @noinspection PhpUnhandledExceptionInspection */
        $result = $parser->parse($this->getRequestXml('unknownOperation'));
        $this->assertEquals('unknownOperation', $result->getOperationName());
        $this->assertNull($result->getOperation());
    }

    /**
     * @param string|null $msgId
     * @return string
     */
    protected function getReplaceRequestXml($msgId)
    {
        $msgIdXml = $msgId
            ? '<imsx_messageIdentifier>' . $msgId . '</imsx_messageIdentifier>'
            : '';
        return '<?xml version = "1.0" encoding = "UTF-8"?>
            <imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
              <imsx_POXHeader>
                <imsx_POXRequestHeaderInfo>
                  <imsx_version>V1.0</imsx_version>
                  ' . $msgIdXml . '
                </imsx_POXRequestHeaderInfo>
              </imsx_POXHeader>
              <imsx_POXBody>
                <replaceResultRequest>
                  <testInnerNode />
                </replaceResultRequest>
              </imsx_POXBody>
            </imsx_POXEnvelopeRequest>';
    }

    /**
     * @param string|null $operationNode
     * @return string
     */
    protected function getRequestXml($operationNode)
    {
        $bodyXml = $operationNode
            ? '<imsx_POXBody><' . $operationNode . ' /></imsx_POXBody>'
            : '<imsx_POXBody />';

        return '<?xml version = "1.0" encoding = "UTF-8"?>
            <imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
              <imsx_POXHeader>
                <imsx_POXRequestHeaderInfo>
                  <imsx_version>V1.0</imsx_version>
                  <imsx_messageIdentifier>msg_id</imsx_messageIdentifier>
                </imsx_POXRequestHeaderInfo>
              </imsx_POXHeader>
              ' . $bodyXml . '
            </imsx_POXEnvelopeRequest>';
    }
}

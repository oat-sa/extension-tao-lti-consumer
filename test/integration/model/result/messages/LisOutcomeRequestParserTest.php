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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoLtiConsumer\test\integration\model\result\messages;

use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequestParser;
use oat\taoLtiConsumer\model\result\operations\OperationsCollection;
use oat\taoLtiConsumer\model\result\operations\replace\OperationRequest;
use oat\taoLtiConsumer\model\result\operations\replace\OperationRequestParser;

class LisOutcomeRequestParserTest extends TestCase
{
    /** @var OperationsCollection|MockObject */
    private $operationsCollectionMock;

    public function testParseLti1p3OutcomeBasic()
    {
        $payload = $this->getPayload();
        $subject = new LisOutcomeRequestParser();
        $this->operationsCollectionMock = $this->createMock(OperationsCollection::class);
        $this->operationsCollectionMock
            ->method('getOperationRequestParser')
            ->willReturn(new OperationRequestParser());
        $subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    OperationsCollection::class => $this->operationsCollectionMock,
                ]
            )
        );
        $xml = $subject->parse($payload);
        $this->assertInstanceOf(OperationRequest::class, $xml->getOperation());
        $this->assertSame('lisResultSourcedId', $xml->getOperation()->getSourcedId());
        $this->assertSame('replaceResultRequest', $xml->getOperationName());
        $this->assertSame('messageIdentifier', $xml->getMessageIdentifier());
    }

    private function getPayload(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeRequest xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
    <imsx_POXHeader>
        <imsx_POXRequestHeaderInfo>
            <imsx_version>V1.0</imsx_version>
            <imsx_messageIdentifier>messageIdentifier</imsx_messageIdentifier>
        </imsx_POXRequestHeaderInfo>
    </imsx_POXHeader>
    <imsx_POXBody>
        <replaceResultRequest>
            <resultRecord>
                <sourcedGUID>
                    <sourcedId>lisResultSourcedId</sourcedId>
                </sourcedGUID>
                <result>
                    <resultScore>
                        <language>en</language>
                        <textString>0.1</textString>
                    </resultScore>
                </result>
            </resultRecord>
        </replaceResultRequest>
    </imsx_POXBody>
</imsx_POXEnvelopeRequest>';
    }
}

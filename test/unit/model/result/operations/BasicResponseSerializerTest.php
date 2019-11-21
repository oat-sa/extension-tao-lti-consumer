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

use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseSerializer;
use oat\taoLtiConsumer\model\result\operations\BasicResponse;
use oat\taoLtiConsumer\model\result\operations\failure\BasicResponseSerializer;
use SimpleXMLElement;

class BasicResponseSerializerTest extends TestCase
{
    public function testSerialize()
    {
        /** @var MockObject|BasicResponse $responseMock */
        $responseMock = $this->createMock(BasicResponse::class);

        /** @var MockObject|SimpleXMLElement $response */
        $resultXmlElement = new SimpleXMLElement('<?xml version="1.0"?><rootEl/>');

        /** @var LisOutcomeResponseSerializer|MockObject $lisOutcomeResponseSerializerMock */
        $lisOutcomeResponseSerializerMock = $this->createMock(LisOutcomeResponseSerializer::class);
        $lisOutcomeResponseSerializerMock->expects($this->once())
            ->method('createXmlElement')
            ->with($responseMock, null)
            ->willReturn($resultXmlElement);

        $serializer = new BasicResponseSerializer();
        $serializer->setServiceLocator($this->getServiceLocatorMock([
            LisOutcomeResponseSerializer::class => $lisOutcomeResponseSerializerMock
        ]));

        $xml = $serializer->toXml($responseMock);
        $this->assertSame('<?xml version="1.0"?><rootEl/>', trim(str_replace(PHP_EOL, '', $xml)));
    }
}

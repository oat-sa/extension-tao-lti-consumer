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
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoLtiConsumer\model\result\operations\replace\ResponseSerializer;
use oat\taoLtiConsumer\model\result\operations\replace\Response;

class ResponseSerializerTest extends TestCase
{
    public function testSerialize()
    {
        /** @var MockObject|Response $response */
        $response = $this->createMock(Response::class);
        $response->method('getMessageIdentifier')->willReturn('msg_id');
        $response->method('getCodeMajor')->willReturn('success');
        $response->method('getStatusDescription')->willReturn('st_descr');
        $response->method('getMessageRefIdentifier')->willReturn('m_ref_id');
        $response->method('getOperationRefIdentifier')->willReturn('replaceResultRequest');

        $serializer = new ResponseSerializer();
        $xml = $serializer->toXml($response);

        $dom = new DOMDocument();
        $this->assertTrue($dom->loadXML($xml));
        $this->assertTrue($dom->schemaValidate(
            __DIR__ . '../../../../../../../doc/ltiXsd/OMSv1p0_LTIv1p1Profile_SyncXSD_v1p0.xsd'
        ));
    }
}

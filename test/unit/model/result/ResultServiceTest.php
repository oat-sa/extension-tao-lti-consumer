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

namespace oat\taoLtiConsumer\model\result;

use oat\generis\test\TestCase;
use oat\taoLtiConsumer\model\result\parser\XmlResultParser;

class ResultServiceTest extends TestCase
{
    public function testProcessPayload()
    {
        $expectedData = ['totoA' => 'toto1', 'totoC' => 'toto3'];
        $parser = $this->createMock(XmlResultParser::class);
        $parser->expects($this->once())->method('parse')->with($this->equalTo('payload'))->willReturnSelf();
        $parser->expects($this->once())->method('getRequestType')->willReturn('toto');
        $parser->expects($this->once())->method('getData')->willReturn([$expectedData]);

        $service = new ResultServiceMock();
        $service->setServiceLocator($this->getServiceLocatorMock([XmlResultParser::SERVICE_ID => $parser]));

        $this->assertEquals($expectedData, $service->process('payload'));
    }

    public function testProcessPayloadWithNotImplementedMethod()
    {
        $this->expectException(ResultException::class);
        $this->expectExceptionCode(501);
        $this->expectExceptionMessage('Method not implemented');

        $parser = $this->createMock(XmlResultParser::class);
        $parser->expects($this->once())->method('parse')->with($this->equalTo('payload'))->willReturnSelf();
        $parser->expects($this->once())->method('getRequestType')->willReturn('toto');
        $parser->expects($this->any())->method('getData');

        $service = new ResultService();
        $service->setServiceLocator($this->getServiceLocatorMock([XmlResultParser::SERVICE_ID => $parser]));

        $service->process('payload');
    }
}

class ResultServiceMock extends ResultService
{
    public function toto($data)
    {
        return $data;
    }
}

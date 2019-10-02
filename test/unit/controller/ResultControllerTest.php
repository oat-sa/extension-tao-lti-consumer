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

namespace oat\taoLtiConsumer\test\unit\controller;

use GuzzleHttp\Psr7\ServerRequest;
use oat\generis\test\TestCase;
use oat\taoLtiConsumer\model\result\MessageBuilder;
use oat\taoLtiConsumer\model\result\ResultException;
use oat\taoLtiConsumer\model\result\ResultService;
use oat\taoLtiConsumer\model\result\XmlFormatterService;
use oat\taoLtiConsumer\controller\ResultController;
use GuzzleHttp\Psr7\Response;

class ResultControllerTest extends TestCase
{
    public function testManageResult()
    {
        $payload = 'payload';
        $return = ['success' => true];
        $returnFormatted = 'wonderfull';

        $resultService = $this->getResultService($payload, $return);
        $formatterService = $this->getXmlFormatterService($return, $returnFormatted);

        $controller = $this->getResultController($payload);

        $controller->setServiceLocator($this->getServiceLocatorMock([
            ResultService::class => $resultService,
            XmlFormatterService::class => $formatterService,
        ]));

        $controller->manageResults();
        $result = $controller->getPsrResponse();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals($returnFormatted, $result->getBody()->getContents());
        $this->assertEquals(MessageBuilder::STATUS_SUCCESS, $result->getStatusCode());
    }

    public function testManageResultWithServiceException()
    {
        $payload = 'payload';
        $returnFormatted = 'not-wonderfull';
        $optionalData = ['data' => 'notgood'];
        $code = 500;

        $resultService = $this->getBadResultService($payload, $code, $optionalData);
        $formatterService = $this->getXmlFormatterService($optionalData, $returnFormatted);

        $controller = $this->getResultController($payload);

        $controller->setServiceLocator($this->getServiceLocatorMock([
            ResultService::class => $resultService,
            XmlFormatterService::class => $formatterService,
        ]));

        $controller->manageResults();
        $result = $controller->getPsrResponse();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals($returnFormatted, $result->getBody()->getContents());
        $this->assertEquals($code, $result->getStatusCode());
    }

    /**
     * @param $payload
     * @return ResultControllerMock
     */
    private function getResultController($payload)
    {
        $controller = new ResultControllerMock();
        $controller->setRequest(new ServerRequest('GET', 'tao.test', [], $payload));
        $controller->setResponse(new Response());

        return $controller;
    }

    /**
     * Get result service
     *
     * @param $expected
     * @param $return
     * @return ResultService
     */
    private function getResultService($expected, $return)
    {
        $service = $this->createMock(ResultService::class);
        $service->expects($this->once())
            ->method('processPayload')
            ->with($this->equalTo($expected))
            ->willReturn($return);

        return $service;
    }

    /**
     * Get result service that throw exception
     *
     * @param $expected
     * @param $code
     * @param $optionalData
     * @return ResultService
     */
    private function getBadResultService($expected, $code, $optionalData)
    {
        $service = $this->createMock(ResultService::class);
        $service->expects($this->once())
            ->method('processPayload')
            ->with($this->equalTo($expected))
            ->willThrowException(new ResultException('error', $code, null, $optionalData));

        return $service;
    }

    /**
     * @param $expected
     * @param $return
     * @return XmlFormatterService
     */
    private function getXmlFormatterService($expected, $return)
    {
        $service = $this->createMock(XmlFormatterService::class);
        $service->expects($this->once())
            ->method('getXmlResponse')
            ->with($this->equalTo($expected))
            ->willReturn($return);

        return $service;
    }

}

class ResultControllerMock extends ResultController
{
    public function __construct()
    {
    }
}

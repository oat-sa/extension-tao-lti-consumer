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

use GuzzleHttp\Psr7\Request;
use oat\generis\test\TestCase;
use oat\generis\test\unit\oatbox\log\TestLogger;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\execution\DeliveryExecution;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use oat\taoLtiConsumer\controller\ResultController;
use GuzzleHttp\Psr7\Response;
use oat\taoLtiConsumer\model\classes\ResultService as LtiResultService;
use oat\taoResultServer\models\classes\ResultService;
use oat\oatbox\event\EventManager;

class ResultControllerTest extends TestCase
{
    const PAYLOAD_TEMPLATE = '<?xml version="1.0" encoding="UTF-8"?>
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
                                <textString>{{score}}</textString>
                            </resultScore>
                        </result>
                    </resultRecord>
                </replaceResultRequest>
            </imsx_POXBody>
        </imsx_POXEnvelopeRequest>
    ';

    public function testManageResultWithIncorrectFilledPayload()
    {
        $requestXml = '<?xml version="1.0" encoding="UTF-8"?>
            <imsx_POXEnvelopeRequest xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
                <imsx_POXHeader>
                    <imsx_POXRequestHeaderInfo>
                        <imsx_version>V1.0</imsx_version>
                        <imsx_messageIdentifier>999999123</imsx_messageIdentifier>
                    </imsx_POXRequestHeaderInfo>
                </imsx_POXHeader>
                <imsx_POXBody>
                    <replaceResultRequestFake>
                        <resultRecord>
                            <sourcedGUID>
                                <sourcedId>3124567</sourcedId>
                            </sourcedGUID>
                            <result>
                                <resultScore>
                                    <language>en</language>
                                    <textString>{{score}}</textString>
                                </resultScore>
                            </result>
                        </resultRecord>
                    </replaceResultRequestFake>
                </imsx_POXBody>
            </imsx_POXEnvelopeRequest>
        ';

        $subject = $this->getResultController();
        $result = $subject->manageResult($requestXml);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(501, $result->getStatusCode());
    }

    public function testManageResultWithIncorrectPayload()
    {
        $requestXml = '';

        $subject = $this->getResultController();
        $result = $subject->manageResult($requestXml);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(501, $result->getStatusCode());
    }

    public function testManageResultWithCorrectPayload()
    {
        $requestXml = str_replace('{{score}}', '0.92', self::PAYLOAD_TEMPLATE);

        $subject = $this->getResultController();
        $result = $subject->manageResult($requestXml);
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testManageResultWithScoreLessThanZero()
    {
        $requestXml = str_replace('{{score}}', '-1', self::PAYLOAD_TEMPLATE);

        $subject = $this->getResultController();
        $result = $subject->manageResult($requestXml);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testManageResultWithScoreIsString()
    {
        $requestXml = str_replace('{{score}}', 'string', self::PAYLOAD_TEMPLATE);

        $subject = $this->getResultController();
        $result = $subject->manageResult($requestXml);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testManageResultWithScoreIsHigherThan1()
    {
        $requestXml = str_replace('{{score}}', '2', self::PAYLOAD_TEMPLATE);

        $subject = $this->getResultController();
        $result = $subject->manageResult($requestXml);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testDeliveryExecutionRetrieving()
    {
        $requestXml = str_replace('{{score}}', '0.92', self::PAYLOAD_TEMPLATE);

        $deliveryExecutionMock = $this->getMockBuilder(DeliveryExecution::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIdentifier'])
            ->getMock();
        $deliveryExecutionMock->method('getIdentifier')->willReturn('12345');

        $resultServiceMock = $this->getMockBuilder(ResultService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDeliveryExecutionById'])
            ->getMockForAbstractClass();
        $resultServiceMock->method('getDeliveryExecutionById')
            ->with('3124567')
            ->willReturn($deliveryExecutionMock);

        $eventManagerMock = $this->getMockBuilder(EventManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['trigger'])
            ->getMock();
        $eventManagerMock->method('trigger')->with(ResultController::LIS_SCORE_RECEIVE_EVENT,
            [ResultController::DELIVERY_EXECUTION_ID => '12345'])->willReturn('12345');

        /** @var ServiceLocatorInterface|MockObject $serviceLocator */
        $serviceLocator = $this->getMockBuilder(ServiceManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        $serviceLocator->method('get')
            ->withConsecutive([ResultService::SERVICE_ID], [EventManager::SERVICE_ID])
            ->willReturnOnConsecutiveCalls($resultServiceMock, $eventManagerMock);

        $subject = $this->getResultController();
        $subject->setServiceLocator($serviceLocator);
        $result = $subject->manageResult($requestXml);
    }

    private function getResultController()
    {
        $subject = new ResultController(new LtiResultService());
        $subject->setLogger(new TestLogger());
        return $subject;
    }
}

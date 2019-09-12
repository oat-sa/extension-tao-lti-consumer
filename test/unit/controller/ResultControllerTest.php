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

use common_exception_BadRequest;
use common_exception_Error;
use common_exception_InvalidArgumentType;
use oat\generis\test\TestCase;
use oat\generis\test\unit\oatbox\log\TestLogger;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoResultServer\models\classes\ResultServerService;
use oat\taoResultServer\models\Exceptions\DuplicateVariableException;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use oat\taoLtiConsumer\controller\ResultController;
use GuzzleHttp\Psr7\Response;
use oat\taoLtiConsumer\model\classes\ResultService as LtiResultService;
use oat\oatbox\event\EventManager;
use taoResultServer_models_classes_WritableResultStorage as WritableResultStorage;
use taoResultServer_models_classes_OutcomeVariable as OutcomeVariable;
use oat\taoDelivery\model\execution\ServiceProxy;

class ResultControllerTest extends TestCase
{
    /**
     * Payload template for set of the tests
     */
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

    /**
     * Expected result for Request mode with success payload template
     */
    const EXPECTED_RESPONSE = '<?xml version="1.0" encoding="UTF-8"?>
        <imsx_POXEnvelopeResponse xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
            <imsx_POXHeader>
                <imsx_POXResponseHeaderInfo>
                    <imsx_version>V1.0</imsx_version>
                    <imsx_messageIdentifier>999999123</imsx_messageIdentifier>
                    <imsx_statusInfo>
                        <imsx_codeMajor>success</imsx_codeMajor>
                        <imsx_severity>status</imsx_severity>
                        <imsx_description>Score for 3124567 is now 0.92</imsx_description>
                        <imsx_messageRefIdentifier>3124567</imsx_messageRefIdentifier>
                        <imsx_operationRefIdentifier>replaceResult</imsx_operationRefIdentifier>
                    </imsx_statusInfo>
                </imsx_POXResponseHeaderInfo>
            </imsx_POXHeader>
            <imsx_POXBody>
                <replaceResultResponse />
            </imsx_POXBody>
        </imsx_POXEnvelopeResponse>
    ';

    /**
     * @var MockObject for ServiceLocator
     */
    private $serviceLocator;

    /**
     * @throws DuplicateVariableException
     * @throws common_exception_Error
     * @throws common_exception_InvalidArgumentType
     */
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
        $result = $subject->storeScore($requestXml);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(501, $result->getStatusCode());
    }

    /**
     * @throws DuplicateVariableException
     * @throws common_exception_Error
     * @throws common_exception_InvalidArgumentType
     */
    public function testManageResultWithIncorrectPayload()
    {
        $requestXml = '';

        $subject = $this->getResultController();
        $result = $subject->storeScore($requestXml);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(501, $result->getStatusCode());
    }

    /**
     * Set of input args for tests
     * @return array
     */
    public function queryScoresToTest()
    {
        return [
            ['{{score}}', '-1', 400],
            ['{{score}}', 'string', 400],
            ['{{score}}', '2', 400],
            ['{{score}}', '0.92', 201],
            [['{{score}}', '3124567'], ['0.92', '3124568'], 404],
        ];
    }

    /**
     * @dataProvider queryScoresToTest
     *
     * @param $search
     * @param $score
     * @param $expectedStatus
     * @throws common_exception_Error
     * @throws common_exception_InvalidArgumentType
     * @throws DuplicateVariableException
     */
    public function testManageResultWithScores($search, $score, $expectedStatus)
    {
        $requestXml = str_replace($search, $score, self::PAYLOAD_TEMPLATE);

        $subject = $this->getResultController();
        $result = $subject->storeScore($requestXml);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals($expectedStatus, $result->getStatusCode());
    }

    /**
     * @throws common_exception_InvalidArgumentType
     */
    public function testGetScoreVariable()
    {
        $subject = new LtiResultService();
        $result = $subject->getScoreVariable(['score' => '0.92']);
        $this->assertInstanceOf(OutcomeVariable::class, $result);
        $this->assertEquals('0.92', $result->getValue());
    }

    /**
     * @throws DuplicateVariableException
     * @throws common_exception_BadRequest
     * @throws common_exception_Error
     * @throws common_exception_InvalidArgumentType
     */
    public function testWrongRequestType()
    {
        $subject = $this->getResultController();

        $this->expectException(common_exception_BadRequest::class);
        $subject->manageResult();
    }

    /**
     * @throws DuplicateVariableException
     * @throws common_exception_BadRequest
     * @throws common_exception_Error
     * @throws common_exception_InvalidArgumentType
     */
    public function testRequestResult()
    {
        $serverRequestMock = $this->getRequestMock();
        $subject = $this->getResultController();
        $subject->setRequest($serverRequestMock);
        $result = $subject->manageResult();
        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(201, $result->getStatusCode());
        $this->assertEquals(self::EXPECTED_RESPONSE, $result->getBody()->getContents());
    }

    /**
     * @return MockObject
     */
    private function getRequestMock()
    {
        $payload = str_replace('{{score}}', '0.92', self::PAYLOAD_TEMPLATE);
        $serverRequestMock = $this->getMockBuilder(ServerRequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBody', 'getServerParams'])
            ->getMockForAbstractClass();
        $serverRequestMock->method('getBody')->willReturn($payload);
        $serverRequestMock->method('getServerParams')->willReturn([
            'HTTP_X_REQUESTED_WITH' => 'xmlhttprequest',
        ]);

        return $serverRequestMock;
    }

    /**
     * @return ResultController
     */
    private function getResultController()
    {
        $ltiResultService = new LtiResultService();
        $ltiResultService->setServiceLocator($this->getServiceLocator());
        $subject = new ResultController($ltiResultService);
        $subject->setLogger(new TestLogger());
        $subject->setServiceLocator($this->getServiceLocator());
        return $subject;
    }

    /**
     * @return MockObject|ServiceLocatorInterface
     */
    private function getServiceLocator()
    {
        if (is_object($this->serviceLocator)) {
            return $this->serviceLocator;
        }

        $deliveryExecutionId = '3124567';
        $sourcedId = '3124567';

        $deliveryExecutionMock = $this->getMockBuilder(DeliveryExecution::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIdentifier'])
            ->getMock();
        $deliveryExecutionMock->method('getIdentifier')->willReturn($deliveryExecutionId);

        $serviceProxyMock = $this->getMockBuilder(ServiceProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDeliveryExecution'])
            ->getMockForAbstractClass();
        $serviceProxyMock->method('getDeliveryExecution')
            ->with($sourcedId)
            ->willReturn($deliveryExecutionMock);

        $eventManagerMock = $this->getMockBuilder(EventManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['trigger'])
            ->getMock();
        $eventManagerMock->method('trigger')->with(ResultController::LIS_SCORE_RECEIVE_EVENT,
            [ResultController::DELIVERY_EXECUTION_ID => $deliveryExecutionId])->willReturn($deliveryExecutionId);

        $resultStorageServiceMock = $this->getMockBuilder(WritableResultStorage::class)
            ->disableOriginalConstructor()
            ->setMethods(['storeTestVariable'])
            ->getMockForAbstractClass();
        $resultStorageServiceMock->method('storeTestVariable')
            ->with($deliveryExecutionId, '', $this->anything(), '')
            ->willReturn(true);

        $resultServerServiceMock = $this->getMockBuilder(ResultServerService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResultStorage'])
            ->getMockForAbstractClass();
        $resultServerServiceMock->method('getResultStorage')
            ->with($sourcedId)->willReturn($resultStorageServiceMock);

        /** @var ServiceLocatorInterface|MockObject $serviceLocator */
        $serviceLocator = $this->getMockBuilder(ServiceManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        $serviceLocator->method('get')
            ->withConsecutive([ServiceProxy::SERVICE_ID], [ResultServerService::SERVICE_ID], [EventManager::SERVICE_ID])
            ->willReturnOnConsecutiveCalls($serviceProxyMock, $resultServerServiceMock, $eventManagerMock);

        $this->serviceLocator = $serviceLocator;

        return $serviceLocator;
    }
}

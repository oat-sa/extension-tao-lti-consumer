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

namespace oat\taoLtiConsumer\test\integration\controller;

use common_exception_BadRequest;
use common_exception_Error;
use common_exception_InvalidArgumentType;
use GuzzleHttp\Psr7\ServerRequest;
use oat\generis\persistence\PersistenceManager;
use oat\generis\test\GenerisTestCase;
use oat\generis\test\TestCase;
use oat\generis\test\unit\oatbox\log\TestLogger;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\Service;
use oat\taoLtiConsumer\model\result\parser\XmlResultParser;
use oat\taoLtiConsumer\model\result\XmlFormatterService;
use oat\taoOutcomeRds\model\RdsResultStorage;
use oat\taoResultServer\models\classes\ResultServerService;
use oat\taoResultServer\models\Exceptions\DuplicateVariableException;
use oat\taoSync\model\Mapper\OfflineResultToOnlineResultMapper;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use oat\taoLtiConsumer\controller\ResultController;
use GuzzleHttp\Psr7\Response;
use oat\taoLtiConsumer\model\result\ResultService as LtiResultService;
use oat\oatbox\event\EventManager;
use taoResultServer_models_classes_WritableResultStorage as WritableResultStorage;
use taoResultServer_models_classes_OutcomeVariable as OutcomeVariable;
use oat\taoDelivery\model\execution\ServiceProxy;
use Psr\Http\Message\StreamInterface;
use oat\taoOutcomeRds\scripts\install\createTables;


class ResultControllerTest extends GenerisTestCase
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
        $payload = '<?xml version="1.0" encoding="UTF-8"?>
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

        $subject = $this->getResultController($payload);

        $result = $subject->manageResult();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(501, $result->getStatusCode());
    }

    public function testManageResultsWithoutPreload()
    {
        $payload = '<?xml version="1.0" encoding="UTF-8"?>
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
                                        <sourcedId>3124567234gdgdgd</sourcedId>
                                    </sourcedGUID>
                                    <result>
                                        <resultScore>
                                            <language>en</language>
                                            <textString>0.69</textString>
                                        </resultScore>
                                    </result>
                                </resultRecord>
                            </replaceResultRequest>
                        </imsx_POXBody>
                    </imsx_POXEnvelopeRequest>';

        $controller = new ResultControllerMock();
        $controller->setServiceLocator($this->getServiceLocator());
        $controller->setRequest(new ServerRequest('GET', 'tao.test', [], $payload));
        $controller->setResponse(new Response());
        $controller->manageResult();

        $result = $controller->getPsrResponse()->getBody()->getContents();

        print_r($result);
        die();
    }

    const DELIVERY_EXECUTION_ID = 'del1';

    /**
     * @return MockObject|ServiceLocatorInterface
     */
    private function getServiceLocator()
    {
        if (is_object($this->serviceLocator)) {
            return $this->serviceLocator;
        }

        $de = $this->prophesize(DeliveryExecutionInterface::class);

        $deImpl = $this->prophesize(DeliveryExecution::class);
        $deImpl->getImplementation()->willReturn($de->reveal());
        $deImpl->getIdentifier()->willReturn(self::DELIVERY_EXECUTION_ID);
        $deImpl->getDelivery()->willReturn($this->prophesize(\core_kernel_classes_Resource::class)->reveal());

        $resultServer = $this->prophesize(ResultServerService::class);
        $storage = new RdsResultStorage([RdsResultStorage::OPTION_PERSISTENCE => 'result']);
        $resultServer->getResultStorage(Argument::any())->willReturn($storage);

        $proxyService = $this->prophesize(ServiceProxy::class);
        $proxyService->getDeliveryExecution(Argument::any())->willReturn($deImpl->reveal());

        $this->serviceLocator = $this->getServiceLocatorMock([
            ServiceProxy::SERVICE_ID => $proxyService->reveal(),
//            EventManager::SERVICE_ID => new EventManager(),
//            ResultServerService::SERVICE_ID => $resultServer->reveal(),
//            PersistenceManager::SERVICE_ID => $this->getSqlMock('result'),
//            RdsResultStorage::SERVICE_ID => $storage,
        ]);

        return $this->serviceLocator;
    }

    private function getResultService()
    {

        $storage->setServiceLocator($this->serviceLocator);
        $installer = new createTables();
        $installer->setServiceLocator($this->serviceLocator);
        $installer([]);


        $service = new LtiResultService();
        $service->setServiceLocator($serviceLocator);
        $service->setModel($onto);
        return $service;
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
    public function ignore_testManageResultWithScores($search, $score, $expectedStatus)
    {
        $requestXml = str_replace($search, $score, self::PAYLOAD_TEMPLATE);

        $subject = $this->getResultController();
        $result = $subject->storeScore($requestXml);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals($expectedStatus, $result->getStatusCode());
    }

    /**
     * @return MockObject
     */
    private function getRequestMock()
    {
        $payload = str_replace('{{score}}', '0.92', self::PAYLOAD_TEMPLATE);
        $streamInterfaceMock = $this->getMockBuilder(StreamInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContents'])
            ->getMockForAbstractClass();
        $streamInterfaceMock->method('getContents')->willReturn($payload);

        $serverRequestMock = $this->getMockBuilder(ServerRequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBody', 'getServerParams', 'getHeader', 'hasHeader'])
            ->getMockForAbstractClass();
        $serverRequestMock->method('getBody')->willReturn($streamInterfaceMock);
        $serverRequestMock->method('getServerParams')->willReturn([
            'HTTP_X_REQUESTED_WITH' => 'xmlhttprequest',
        ]);
        $serverRequestMock->method('getHeader')
            ->withConsecutive(['Accept'])
            ->willReturnOnConsecutiveCalls('*');
        $serverRequestMock->method('hasHeader')
            ->withConsecutive(['Accept'], ['REQUEST_METHOD'])
            ->willReturnOnConsecutiveCalls(true, true);

        return $serverRequestMock;
    }

    /**
     * @return MockObject|ServiceLocatorInterface
     */
    private function getResultController($payload = null)
    {
        $controller = new ResultControllerMock();
        $controller->setRequest(new ServerRequest('GET', 'tao.test', [], $payload));
        $controller->setResponse(new Response());
        $controller->setServiceLocator($this->getServiceLocatorMock([
            ResultService::SERVICE_ID => $this->getResultService(),
            XmlFormatterService::class => new XmlFormatterService(),
        ]));

        return $controller;
    }
}

class ResultControllerMock extends ResultController
{
    public function __construct()
    {
    }
}

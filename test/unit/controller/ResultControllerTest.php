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

use common_exception_MethodNotAllowed;
use Exception;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoLti\models\classes\Lis\LisAuthAdapter;
use oat\taoLti\models\classes\Lis\LisAuthAdapterException;
use oat\taoLti\models\classes\Lis\LisAuthAdapterFactory;
use oat\taoLti\models\classes\Lis\LtiProviderUser;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLtiConsumer\controller\ResultController;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequest;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequestParser;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseInterface;
use oat\taoLtiConsumer\model\result\operations\failure\BasicResponseSerializer;
use oat\taoLtiConsumer\model\result\operations\OperationsCollection;
use oat\taoLtiConsumer\model\result\operations\ResponseSerializerInterface;
use oat\taoLtiConsumer\model\result\ParsingException;
use oat\taoLtiConsumer\model\result\ResultService as LtiResultService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use tao_models_classes_UserException;

class ResultControllerTest extends TestCase
{
    /**
     * @throws tao_models_classes_UserException
     */
    public function testManageResultMethod()
    {
        $exceptions = 0;
        $methods = ['GET', 'PUT', 'DELETE'];
        foreach ($methods as $method) {

            /** @var ServerRequestInterface|MockObject $requestMock */
            $requestMock = $this->createMock(ServerRequestInterface::class);
            $requestMock->method('getMethod')->willReturn($method);

            $controller = new ResultController();
            $controller->setRequest($requestMock);

            try {
                $controller->manageResults();
            } catch (common_exception_MethodNotAllowed $exception) {
                ++$exceptions;
                $this->assertSame(['POST'], $exception->getAllowedMethods());
            }
        }
        $this->assertSame(count($methods), $exceptions);
    }

    /**
     * @throws common_exception_MethodNotAllowed
     * @throws tao_models_classes_UserException
     */
    public function testManageResultAuthError()
    {
        /** @var LisAuthAdapter|MockObject $listAuthAdapterMock */
        $listAuthAdapterMock = $this->createMock(LisAuthAdapter::class);
        $listAuthAdapterMock->method('authenticate')->willThrowException(new LisAuthAdapterException('m'));

        /** @var LisAuthAdapterFactory|MockObject $ltiResultServiceMock */
        $lisAuthAdapterFactory = $this->createMock(LisAuthAdapterFactory::class);
        $lisAuthAdapterFactory->method('create')->willReturn($listAuthAdapterMock);

        /** @var ServerRequestInterface|MockObject $requestMock */
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getMethod')->willReturn('POST');

        $controller = new ResultController();
        $controller->setServiceLocator($this->getServiceLocatorMock([
            LisAuthAdapterFactory::class => $lisAuthAdapterFactory
        ]));
        $controller->setRequest($requestMock);
        $this->expectException(tao_models_classes_UserException::class);
        $controller->manageResults();
    }

    /**
     * @throws common_exception_MethodNotAllowed
     * @throws tao_models_classes_UserException
     */
    public function testManageResultAuthInternalError()
    {
        /** @var LisAuthAdapter|MockObject $listAuthAdapterMock */
        $listAuthAdapterMock = $this->createMock(LisAuthAdapter::class);
        $listAuthAdapterMock->method('authenticate')->willThrowException(new Exception('m'));

        /** @var LisAuthAdapterFactory|MockObject $ltiResultServiceMock */
        $lisAuthAdapterFactory = $this->createMock(LisAuthAdapterFactory::class);
        $lisAuthAdapterFactory->method('create')->willReturn($listAuthAdapterMock);

        /** @var ServerRequestInterface|MockObject $requestMock */
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getMethod')->willReturn('POST');

        /** @var ResponseInterface|MockObject $responseMock */
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('withStatus')->with(500)->willReturn($responseMock);
        $responseMock->method('withHeader')->with('Content-Type', 'application/xml')->willReturn($responseMock);
        $responseMock->method('withBody')
            ->with($this->callback(function (StreamInterface $stream) {
                return 'xmlout' === (string) $stream;
            }))
            ->willReturn($responseMock);

        /** @var BasicResponseSerializer|MockObject $ltiResultServiceMock */
        $basicResponseSerializerMock = $this->createMock(BasicResponseSerializer::class);
        $basicResponseSerializerMock->expects($this->once())
            ->method('toXml')
            ->with($this->callback(function (LisOutcomeResponseInterface $response) {
                return
                    $response->getStatus() === LisOutcomeResponseInterface::STATUS_INTERNAL_ERROR &&
                    $response->getCodeMajor() === LisOutcomeResponseInterface::CODE_MAJOR_FAILURE;
            }))
            ->willReturn('xmlout');

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('error');

        $controller = new ResultController();
        $controller->setServiceLocator($this->getServiceLocatorMock([
            LisAuthAdapterFactory::class => $lisAuthAdapterFactory,
            BasicResponseSerializer::class => $basicResponseSerializerMock
        ]));
        $controller->setLogger($loggerMock);
        $controller->setRequest($requestMock);
        $controller->setResponse($responseMock);
        $controller->manageResults();
        $response = $controller->getPsrResponse();
        $this->assertSame($responseMock, $response);
    }

    /**
     * @throws common_exception_MethodNotAllowed
     * @throws tao_models_classes_UserException
     */
    public function testManageResult()
    {
        /** @var LisOutcomeRequest|MockObject $lisRequestMock */
        $lisRequestMock = $this->createMock(LisOutcomeRequest::class);

        /** @var LisOutcomeRequestParser|MockObject $lisOutcomeRequestParserMock */
        $lisOutcomeRequestParserMock = $this->createMock(LisOutcomeRequestParser::class);
        $lisOutcomeRequestParserMock->expects($this->once())
            ->method('parse')
            ->with('request_body')
            ->willReturn($lisRequestMock);

        /** @var LtiProvider|MockObject $LtiProviderMock */
        $LtiProviderMock = $this->createMock(LtiProvider::class);

        /** @var LtiProviderUser|MockObject $ltiProviderUserMock */
        $ltiProviderUserMock = $this->createMock(LtiProviderUser::class);
        $ltiProviderUserMock->method('getLtiProvider')->willReturn($LtiProviderMock);

        /** @var LisOutcomeResponseInterface|MockObject $lisResponseMock */
        $lisResponseMock = $this->createMock(LisOutcomeResponseInterface::class);
        $lisResponseMock->method('getStatus')->willReturn(LisOutcomeResponseInterface::STATUS_SUCCESS);

        /** @var ResponseSerializerInterface|MockObject $ltiResultServiceMock */
        $responseSerializerMock = $this->createMock(ResponseSerializerInterface::class);
        $responseSerializerMock->expects($this->once())
            ->method('toXml')
            ->with($lisResponseMock)
            ->willReturn('xml_out');

        /** @var OperationsCollection|MockObject $ltiResultServiceMock */
        $operationsCollectionMock = $this->createMock(OperationsCollection::class);
        $operationsCollectionMock->method('getResponseSerializer')
            ->with($lisResponseMock)
            ->willReturn($responseSerializerMock);

        /** @var ResponseInterface|MockObject $responseMock */
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('withStatus')->with(201)->willReturn($responseMock);
        $responseMock->method('withHeader')->with('Content-Type', 'application/xml')->willReturn($responseMock);
        $responseMock->method('withBody')
            ->with($this->callback(function (StreamInterface $stream) {
                return 'xml_out' === (string) $stream;
            }))
            ->willReturn($responseMock);

        /** @var LtiResultService|MockObject $ltiResultServiceMock */
        $ltiResultServiceMock = $this->createMock(LtiResultService::class);
        $ltiResultServiceMock->expects($this->once())
            ->method('process')
            ->with($lisRequestMock, $LtiProviderMock)
            ->willReturn($lisResponseMock);

        /** @var LisAuthAdapter|MockObject $lisAuthAdapterMock */
        $lisAuthAdapterMock = $this->createMock(LisAuthAdapter::class);
        $lisAuthAdapterMock->method('authenticate')->willReturn($ltiProviderUserMock);

        /** @var LisAuthAdapterFactory|MockObject $lisAuthAdapterFactory */
        $lisAuthAdapterFactory = $this->createMock(LisAuthAdapterFactory::class);
        $lisAuthAdapterFactory->method('create')->willReturn($lisAuthAdapterMock);

        /** @var ServerRequestInterface|MockObject $requestMock */
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getMethod')->willReturn('POST');
        $requestMock->method('getBody')->willReturn('request_body');

        $controller = new ResultController();
        $controller->setServiceLocator($this->getServiceLocatorMock([
            LisAuthAdapterFactory::class => $lisAuthAdapterFactory,
            LisOutcomeRequestParser::class => $lisOutcomeRequestParserMock,
            LtiResultService::class => $ltiResultServiceMock,
            OperationsCollection::class => $operationsCollectionMock
        ]));
        $controller->setRequest($requestMock);
        $controller->setResponse($responseMock);
        $controller->manageResults();
        $response = $controller->getPsrResponse();
        $this->assertSame($responseMock, $response);
    }

    /**
     * @throws common_exception_MethodNotAllowed
     * @throws tao_models_classes_UserException
     */
    public function testManageResultParsingException()
    {
        /** @var LisOutcomeRequestParser|MockObject $lisOutcomeRequestParserMock */
        $lisOutcomeRequestParserMock = $this->createMock(LisOutcomeRequestParser::class);
        $lisOutcomeRequestParserMock->expects($this->once())
            ->method('parse')
            ->with('request_body')
            ->willThrowException(new ParsingException('mm'));

        /** @var LtiProvider|MockObject $LtiProviderMock */
        $LtiProviderMock = $this->createMock(LtiProvider::class);

        /** @var LtiProviderUser|MockObject $ltiProviderUserMock */
        $ltiProviderUserMock = $this->createMock(LtiProviderUser::class);
        $ltiProviderUserMock->method('getLtiProvider')->willReturn($LtiProviderMock);

        /** @var ResponseSerializerInterface|MockObject $basicResppnseSerializerMock */
        $basicResppnseSerializerMock = $this->createMock(BasicResponseSerializer::class);
        $basicResppnseSerializerMock->expects($this->once())
            ->method('toXml')
            ->with($this->callback(function (LisOutcomeResponseInterface $response) {
                return
                    $response->getStatus() === LisOutcomeResponseInterface::STATUS_INVALID_REQUEST &&
                    $response->getCodeMajor() === LisOutcomeResponseInterface::CODE_MAJOR_FAILURE;
            }))
            ->willReturn('xml_out');

        /** @var ResponseInterface|MockObject $responseMock */
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('withStatus')->with(400)->willReturn($responseMock);
        $responseMock->method('withHeader')->with('Content-Type', 'application/xml')->willReturn($responseMock);
        $responseMock->method('withBody')
            ->with($this->callback(function (StreamInterface $stream) {
                return 'xml_out' === (string) $stream;
            }))
            ->willReturn($responseMock);

        /** @var LisAuthAdapter|MockObject $listAuthAdapterMock */
        $listAuthAdapterMock = $this->createMock(LisAuthAdapter::class);
        $listAuthAdapterMock->method('authenticate')->willReturn($ltiProviderUserMock);

        /** @var LisAuthAdapterFactory|MockObject LisAuthAdapterFactory */
        $lisAuthAdapterFactory = $this->createMock(LisAuthAdapterFactory::class);
        $lisAuthAdapterFactory->method('create')->willReturn($listAuthAdapterMock);

        /** @var ServerRequestInterface|MockObject $requestMock */
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getMethod')->willReturn('POST');
        $requestMock->method('getBody')->willReturn('request_body');

        $controller = new ResultController();
        $controller->setServiceLocator($this->getServiceLocatorMock([
            LisAuthAdapterFactory::class => $lisAuthAdapterFactory,
            LisOutcomeRequestParser::class => $lisOutcomeRequestParserMock,
            BasicResponseSerializer::class => $basicResppnseSerializerMock
        ]));
        $controller->setRequest($requestMock);
        $controller->setResponse($responseMock);
        $controller->manageResults();
        $response = $controller->getPsrResponse();
        $this->assertSame($responseMock, $response);
    }
}

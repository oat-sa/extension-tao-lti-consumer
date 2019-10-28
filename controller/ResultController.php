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

namespace oat\taoLtiConsumer\controller;

use common_exception_Error;
use common_exception_MethodNotAllowed;
use common_exception_NotFound;
use common_user_auth_AuthFailedException;
use common_user_User;
use oat\taoLti\models\classes\Lis\LisAuthAdapter;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequest;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequestParser;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseInterface;
use oat\taoLtiConsumer\model\result\operations\BasicResponse;
use oat\taoLtiConsumer\model\result\operations\BasicResponseSerializer;
use oat\taoLtiConsumer\model\result\operations\OperationsCollection;
use oat\taoLtiConsumer\model\result\ParsingException;
use oat\taoLtiConsumer\model\result\ResultService as LtiResultService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Request;
use Slim\Http\StatusCode;
use tao_actions_CommonModule;
use Throwable;
use function GuzzleHttp\Psr7\stream_for;

class ResultController extends tao_actions_CommonModule
{
    public const XML_CONTENT_TYPE = 'application/xml';

    /**
     * @noinspection PhpUnused
     * @throws common_exception_MethodNotAllowed
     * @throws common_user_auth_AuthFailedException
     */
    public function manageResults()
    {
        if (!$this->isRequestPost()) {
            throw new common_exception_MethodNotAllowed(null, 0, [Request::HTTP_POST]);
        }

        $this->authenticate($this->getPsrRequest());
        $payload = $this->getPsrRequest()->getBody()->getContents();
        $requestParser = $this->getRequestParser();

        try {
            $lisRequest = $requestParser->parse($payload);
            $this->response = $this->processLisRequest($lisRequest);
        } catch (ParsingException $parsingException) {
            $this->response = $this->createParseErrorResponse($parsingException);
        } catch (Throwable $throwable) {
            $this->response = $this->createInternalErrorResponse($throwable);
        }
    }

    /**
     * @param LisOutcomeRequest $lisRequest
     * @return ResponseInterface
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    private function processLisRequest(LisOutcomeRequest $lisRequest)
    {
        $lisResponse = $this->getLtiResultService()->process($lisRequest);

        $serializer = $this->getOperationsCollection()->getResponseSerializer($lisResponse);
        if ($serializer === null) {
            return $this->createInternalErrorResponse();
        }
        $xml = $serializer->toXml($lisResponse);
        $httpStatus = $this->mapLisResponseStatusToHttp($lisResponse->getStatus());
        return $this->getXmlResponse($httpStatus, $xml);
    }

    /**
     * @param string $lisResponseStatus
     * @return int
     */
    private function mapLisResponseStatusToHttp($lisResponseStatus)
    {
        switch ($lisResponseStatus) {
            case LisOutcomeResponseInterface::STATUS_SUCCESS: return StatusCode::HTTP_OK;
            case LisOutcomeResponseInterface::STATUS_INVALID_REQUEST: return StatusCode::HTTP_BAD_REQUEST;
            case LisOutcomeResponseInterface::STATUS_NOT_FOUND: return StatusCode::HTTP_NOT_FOUND;
            case LisOutcomeResponseInterface::STATUS_UNSUPPORTED: return StatusCode::HTTP_NOT_IMPLEMENTED;
            // including LisOutcomeResponseInterface::STATUS_INTERNAL_ERROR
            default: return StatusCode::HTTP_INTERNAL_SERVER_ERROR;
        }
    }

    /**
     * @param ParsingException $parsingException
     * @return ResponseInterface
     */
    private function createParseErrorResponse(ParsingException $parsingException)
    {
        return $this->getXmlFailureResponse(
            StatusCode::HTTP_BAD_REQUEST,
            'Invalid input xml: ' . $parsingException->getMessage()
            );
    }

    /**
     * @param Throwable $throwable
     * @return ResponseInterface
     */
    private function createInternalErrorResponse(Throwable $throwable = null)
    {
        if ($throwable !== null) {
            $this->logError('Internal error during lti outcome request. ' . $throwable->getMessage());
        }
        return $this->getXmlFailureResponse(
            StatusCode::HTTP_INTERNAL_SERVER_ERROR,
            'Internal error'
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @return common_user_User
     * @throws common_user_auth_AuthFailedException
     */
    private function authenticate(ServerRequestInterface $request)
    {
        /** @var LisAuthAdapter $adaptor */
        $adaptor = $this->propagate(new LisAuthAdapter($request));
        return $adaptor->authenticate();
    }

    /**
     * @param int $statusCode
     * @param string $xml
     * @return ResponseInterface
     */
    private function getXmlResponse($statusCode, $xml)
    {
        return $this->getPsrResponse()
            ->withStatus($statusCode)
            ->withHeader('Content-Type', self::XML_CONTENT_TYPE)
            ->withBody(stream_for($xml));
    }

    private function getXmlFailureResponse($statusCode, $statusDescription)
    {
        $serializer = $this->getBasicResponseSerializer();
        $xml = $serializer->toXml(new BasicResponse(
            LisOutcomeResponseInterface::STATUS_INTERNAL_ERROR, // shouldn't be serialized
            $statusDescription,
            LisOutcomeResponseInterface::CODE_MAJOR_FAILURE
        ));
        return $this->getXmlResponse($statusCode, $xml);
    }

    /**
     * @return LtiResultService
     */
    private function getLtiResultService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(LtiResultService::class);
    }

    /**
     * @return LisOutcomeRequestParser
     */
    private function getRequestParser()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(LisOutcomeRequestParser::class);
    }

    /**
     * @return BasicResponseSerializer
     */
    private function getBasicResponseSerializer()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(BasicResponseSerializer::class);
    }

    /**
     * @return OperationsCollection
     */
    protected function getOperationsCollection()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(OperationsCollection::class);
    }
}

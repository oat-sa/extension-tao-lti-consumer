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
use oat\taoLti\models\classes\Lis\LisAuthAdapterFactory;
use oat\taoLti\models\classes\Lis\LtiProviderUser;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequest;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequestParser;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseInterface;
use oat\taoLtiConsumer\model\result\operations\BasicResponse;
use oat\taoLtiConsumer\model\result\operations\failure\BasicResponseSerializer;
use oat\taoLtiConsumer\model\result\operations\OperationsCollection;
use oat\taoLtiConsumer\model\result\ParsingException;
use oat\taoLtiConsumer\model\result\ResultService as LtiResultService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Request;
use Slim\Http\StatusCode;
use tao_actions_CommonModule;
use tao_models_classes_UserException;
use Throwable;
use function GuzzleHttp\Psr7\stream_for;

class ResultController extends tao_actions_CommonModule
{
    public const XML_CONTENT_TYPE = 'application/xml';

    /**
     * @throws common_exception_MethodNotAllowed
     * @throws tao_models_classes_UserException
     */
    public function manageResults()
    {
        if (!$this->isRequestPost()) {
            throw new common_exception_MethodNotAllowed(null, 0, [Request::HTTP_POST]);
        }

        try {
            $user = $this->authorizeUser($this->getPsrRequest());
        } catch (tao_models_classes_UserException $userException) {
            throw $userException;
        } catch (Throwable $throwable) {
            $this->response = $this->createInternalErrorResponse($throwable);
            return;
        }

        $payload = (string) $this->getPsrRequest()->getBody();
        $requestParser = $this->getRequestParser();

        try {
            $lisRequest = $requestParser->parse($payload);
            $this->response = $this->processLisRequest(
                $lisRequest,
                $user->getLtiProvider()
            );
        } catch (ParsingException $parsingException) {
            $this->response = $this->createParseErrorResponse($parsingException);
        } catch (Throwable $throwable) {
            $this->response = $this->createInternalErrorResponse($throwable);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return LtiProviderUser
     * @throws tao_models_classes_UserException
     */
    private function authorizeUser(ServerRequestInterface $request)
    {
        try {
            return $this->getLisAuthAdapterFactory()->create($request)->authenticate();
        } catch (common_user_auth_AuthFailedException $authFailedException) {
            throw new tao_models_classes_UserException($authFailedException->getMessage());
        }
    }

    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    private function processLisRequest(LisOutcomeRequest $lisRequest, LtiProvider $ltiProvider): ResponseInterface
    {
        $lisResponse = $this->getLtiResultService()->process($lisRequest, $ltiProvider);

        $serializer = $this->getOperationsCollection()->getResponseSerializer($lisResponse);
        if ($serializer === null) {
            return $this->createInternalErrorResponse();
        }
        $xml = $serializer->toXml($lisResponse);
        $httpStatus = $this->mapLisResponseStatusToHttp($lisResponse->getStatus());
        return $this->getXmlResponse($httpStatus, $xml);
    }

    private function mapLisResponseStatusToHttp(string $lisResponseStatus): int
    {
        switch ($lisResponseStatus) {
            case LisOutcomeResponseInterface::STATUS_SUCCESS:
                return StatusCode::HTTP_CREATED;
            case LisOutcomeResponseInterface::STATUS_INVALID_REQUEST:
                return StatusCode::HTTP_BAD_REQUEST;
            case LisOutcomeResponseInterface::STATUS_NOT_FOUND:
                return StatusCode::HTTP_NOT_FOUND;
            case LisOutcomeResponseInterface::STATUS_UNSUPPORTED:
                return StatusCode::HTTP_NOT_IMPLEMENTED;
            // including LisOutcomeResponseInterface::STATUS_INTERNAL_ERROR
            default:
                return StatusCode::HTTP_INTERNAL_SERVER_ERROR;
        }
    }

    private function createParseErrorResponse(ParsingException $parsingException): ResponseInterface
    {
        return $this->getXmlFailureResponse(
            StatusCode::HTTP_BAD_REQUEST,
            LisOutcomeResponseInterface::STATUS_INVALID_REQUEST,
            'Invalid input xml: ' . $parsingException->getMessage(),
            $parsingException->getXmlMessageId()
            );
    }

    private function createInternalErrorResponse(Throwable $throwable = null): ResponseInterface
    {
        if ($throwable !== null) {
            $this->logError('Internal error during lti outcome request. ' . $throwable->getMessage());
            $this->logError($throwable->getTraceAsString());
        }
        return $this->getXmlFailureResponse(
            StatusCode::HTTP_INTERNAL_SERVER_ERROR,
            LisOutcomeResponseInterface::STATUS_INTERNAL_ERROR,
            'Internal error'
        );
    }

    private function getXmlResponse(int $statusCode, string $xml): ResponseInterface
    {
        return $this->getPsrResponse()
            ->withStatus($statusCode)
            ->withHeader('Content-Type', self::XML_CONTENT_TYPE)
            ->withBody(stream_for($xml));
    }

    /**
     * @see LisOutcomeResponseInterface
     */
    private function getXmlFailureResponse(int $statusCode, string $xmlStatus, string $statusDescription, string $messageRefIdentifier = null): ResponseInterface
    {
        $serializer = $this->getBasicResponseSerializer();
        $xml = $serializer->toXml(new BasicResponse(
            $xmlStatus,
            $statusDescription,
            LisOutcomeResponseInterface::CODE_MAJOR_FAILURE,
            null,
            $messageRefIdentifier
        ));
        return $this->getXmlResponse($statusCode, $xml);
    }

    private function getLtiResultService(): LtiResultService
    {
        return $this->getServiceLocator()->get(LtiResultService::class);
    }

    private function getRequestParser(): LisOutcomeRequestParser
    {
        return $this->getServiceLocator()->get(LisOutcomeRequestParser::class);
    }

    private function getBasicResponseSerializer(): BasicResponseSerializer
    {
        return $this->getServiceLocator()->get(BasicResponseSerializer::class);
    }

    protected function getOperationsCollection(): OperationsCollection
    {
        return $this->getServiceLocator()->get(OperationsCollection::class);
    }

    protected function getLisAuthAdapterFactory(): LisAuthAdapterFactory
    {
        return $this->getServiceLocator()->get(LisAuthAdapterFactory::class);
    }
}

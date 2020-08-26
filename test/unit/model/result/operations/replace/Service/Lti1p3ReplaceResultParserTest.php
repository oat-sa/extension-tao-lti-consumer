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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoLtiConsumer\test\unit\model\result\operations\replace\Service;

use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Service\Server\Validator\AccessTokenRequestValidationResult;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;
use oat\taoLti\models\classes\Security\DataAccess\Service\AccessTokenRequestValidator;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequest;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequestParser;
use oat\taoLtiConsumer\model\result\operations\replace\Service\Lti1p3ReplaceResultParser;
use Psr\Http\Message\ServerRequestInterface;
use tao_models_classes_UserException;

class Lti1p3ReplaceResultParserTest extends TestCase
{
    /** @var Lti1p3ReplaceResultParser */
    private $subject;

    /** @var LisOutcomeRequestParser|MockObject */
    private $lisOutcomeRequestParserMock;

    /** @var LtiProviderService|MockObject */
    private $ltiProviderServiceMock;

    /** @var MockObject|ServerRequestInterface */
    private $requestMock;

    /** @var AccessTokenRequestValidator|MockObject */
    private $accessTokenRequestValidatorMock;

    /** @var AccessTokenRequestValidationResult|MockObject */
    private $accessTokenRequestValidationResultMock;

    protected function setUp(): void
    {
        $this->subject = new Lti1p3ReplaceResultParser();

        $this->lisOutcomeRequestParserMock = $this->createMock(LisOutcomeRequestParser::class);
        $this->ltiProviderServiceMock = $this->createMock(LtiProviderService::class);
        $this->accessTokenRequestValidatorMock = $this->createMock(AccessTokenRequestValidator::class);

        $this->accessTokenRequestValidationResultMock = $this->createMock(AccessTokenRequestValidationResult::class);

        $this->requestMock = $this->createMock(ServerRequestInterface::class);

        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    LisOutcomeRequestParser::class => $this->lisOutcomeRequestParserMock,
                    LtiProviderService::SERVICE_ID => $this->ltiProviderServiceMock,
                    AccessTokenRequestValidator::class => $this->accessTokenRequestValidatorMock,
                ]
            )
        );
    }

    public function testParse()
    {

        $registrationMock = $this->createMock(RegistrationInterface::class);
        $lisOutcomeRequestMock = $this->createMock(LisOutcomeRequest::class);
        $ltiProviderMock = $this->createMock(LtiProvider::class);

        $this->ltiProviderServiceMock
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([$ltiProviderMock]);

        $this->lisOutcomeRequestParserMock
            ->expects($this->once())
            ->method('parse')
            ->willReturn($lisOutcomeRequestMock);

        $this->accessTokenRequestValidatorMock
            ->expects($this->once())
            ->method('validate')
            ->willReturn($this->accessTokenRequestValidationResultMock);

        $this->accessTokenRequestValidationResultMock
            ->expects($this->once())
            ->method('hasError')
            ->willReturn(false);

        $this->accessTokenRequestValidationResultMock
            ->expects($this->once())
            ->method('getRegistration')
            ->willReturn($registrationMock);

        $this->subject->parse($this->requestMock);
    }

    public function testParseValidationHasErrors()
    {
        $this->expectException(tao_models_classes_UserException::class);

        $this->accessTokenRequestValidatorMock
            ->expects($this->once())
            ->method('validate')
            ->willReturn($this->accessTokenRequestValidationResultMock);

        $this->accessTokenRequestValidationResultMock
            ->expects($this->once())
            ->method('hasError')
            ->willReturn(true);

        $this->subject->parse($this->requestMock);
    }
}

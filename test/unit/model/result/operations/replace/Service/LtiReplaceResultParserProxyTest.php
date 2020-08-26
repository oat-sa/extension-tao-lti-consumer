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
use OAT\Library\Lti1p3Core\Service\Server\Validator\AccessTokenRequestValidator;
use oat\taoLtiConsumer\model\result\operations\replace\ReplaceResultOperationRequest;
use oat\taoLtiConsumer\model\result\operations\replace\Service\Lti1p1ReplaceResultParser;
use oat\taoLtiConsumer\model\result\operations\replace\Service\Lti1p3ReplaceResultParser;
use oat\taoLtiConsumer\model\result\operations\replace\Service\LtiReplaceResultParserProxy;
use Psr\Http\Message\ServerRequestInterface;

class LtiReplaceResultParserProxyTest extends TestCase
{

    /** @var LtiReplaceResultParserProxy */
    private $subject;

    /** @var Lti1p1ReplaceResultParser|MockObject */
    private $lti1p1ReplaceResultParser;

    /** @var Lti1p3ReplaceResultParser|MockObject */
    private $lti1p3ReplaceResultParser;

    /** @var ServerRequestInterface|MockObject */
    private $requestMock;

    /** @var AccessTokenRequestValidator|MockObject */
    private $accessTokenRequestValidatorMock;

    /** @var AccessTokenRequestValidationResult|MockObject */
    private $accessTokenRequestValidationResultMock;

    /** @var RegistrationInterface|MockObject */
    private $registrationMock;

    /** @var ReplaceResultOperationRequest|MockObject */
    private $replaceResultOperationRequestMock;

    protected function setUp(): void
    {
        $this->subject = new LtiReplaceResultParserProxy();
        $this->accessTokenRequestValidatorMock = $this->createMock(AccessTokenRequestValidator::class);
        $this->lti1p1ReplaceResultParser = $this->createMock(Lti1p1ReplaceResultParser::class);
        $this->lti1p3ReplaceResultParser = $this->createMock(Lti1p3ReplaceResultParser::class);
        $this->replaceResultOperationRequestMock = $this->createMock(ReplaceResultOperationRequest::class);

        $this->accessTokenRequestValidationResultMock = $this->createMock(AccessTokenRequestValidationResult::class);
        $this->registrationMock = $this->createMock(RegistrationInterface::class);

        $this->requestMock = $this->createMock(ServerRequestInterface::class);

        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    Lti1p1ReplaceResultParser::class => $this->lti1p1ReplaceResultParser,
                    Lti1p3ReplaceResultParser::class => $this->lti1p3ReplaceResultParser,
                    AccessTokenRequestValidator::class => $this->accessTokenRequestValidatorMock,
                ]
            )
        );
    }

    public function testParseIsLti1p3(): void
    {
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
            ->willReturn($this->registrationMock);

        $this->lti1p3ReplaceResultParser
            ->expects($this->once())
            ->method('parse')
            ->willReturn($this->replaceResultOperationRequestMock);

        $this->subject->parse($this->requestMock);
    }

    public function testParseIsLti1p1(): void
    {
        $this->accessTokenRequestValidatorMock
            ->expects($this->once())
            ->method('validate')
            ->willReturn($this->accessTokenRequestValidationResultMock);

        $this->accessTokenRequestValidationResultMock
            ->expects($this->once())
            ->method('hasError')
            ->willReturn(true);

        $this->lti1p1ReplaceResultParser
            ->expects($this->once())
            ->method('parse')
            ->willReturn($this->replaceResultOperationRequestMock);

        $this->subject->parse($this->requestMock);
    }
}

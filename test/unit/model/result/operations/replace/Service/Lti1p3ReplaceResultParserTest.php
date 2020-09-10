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
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;
use oat\taoLti\models\classes\Platform\Service\AccessTokenRequestValidator;
use oat\taoLtiConsumer\model\ltiProvider\repository\DeliveryLtiProviderRepository;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequest;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequestParser;
use oat\taoLtiConsumer\model\result\operations\OperationRequestInterface;
use oat\taoLtiConsumer\model\result\operations\replace\Service\Lti1p3ReplaceResultParser;
use oat\taoLtiConsumer\model\result\ParsingException;
use Psr\Http\Message\ServerRequestInterface;

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

    /** @var LisOutcomeRequest */
    private $lisOutcomeRequestMock;

    protected function setUp(): void
    {
        $this->subject = new Lti1p3ReplaceResultParser();

        $this->lisOutcomeRequestParserMock = $this->createMock(LisOutcomeRequestParser::class);
        $this->ltiProviderServiceMock = $this->createMock(DeliveryLtiProviderRepository::class);
        $this->accessTokenRequestValidatorMock = $this->createMock(AccessTokenRequestValidator::class);
        $this->lisOutcomeRequestMock = $this->createMock(LisOutcomeRequest::class);
        $this->requestMock = $this->createMock(ServerRequestInterface::class);

        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    LisOutcomeRequestParser::class => $this->lisOutcomeRequestParserMock,
                    DeliveryLtiProviderRepository::class=> $this->ltiProviderServiceMock,
                    AccessTokenRequestValidator::class => $this->accessTokenRequestValidatorMock,
                ]
            )
        );
    }

    public function testParse(): void
    {
        $ltiProviderMock = $this->createMock(LtiProvider::class);
        $operationRequestMock = $this->createMock(OperationRequestInterface::class);

        $this->ltiProviderServiceMock
            ->expects($this->once())
            ->method('searchByDeliveryExecutionId')
            ->willReturn($ltiProviderMock);

        $this->lisOutcomeRequestParserMock
            ->expects($this->once())
            ->method('parse')
            ->willReturn($this->lisOutcomeRequestMock);


        $this->lisOutcomeRequestMock
            ->expects($this->exactly(2))
            ->method('getOperation')
            ->willReturn($operationRequestMock);

        $operationRequestMock
            ->expects($this->once())
            ->method('getSourcedId')
            ->willReturn('deliveryExecutionId');

        $this->accessTokenRequestValidatorMock
            ->expects($this->once())
            ->method('withLtiProvider')
            ->willReturn($this->accessTokenRequestValidatorMock);

        $this->accessTokenRequestValidatorMock
            ->expects($this->once())
            ->method('withRole')
            ->willReturn($this->accessTokenRequestValidatorMock);

        $this->accessTokenRequestValidatorMock
            ->expects($this->once())
            ->method('validate');

        $result = $this->subject->parse($this->requestMock);

        $this->assertSame($this->lisOutcomeRequestMock, $result->getLisOutcomeRequest());
        $this->assertSame($ltiProviderMock, $result->getLtiProvider());
    }

    public function testParseOperationMissing(): void
    {
        $this->expectException(ParsingException::class);

        $this->lisOutcomeRequestParserMock
            ->expects($this->once())
            ->method('parse')
            ->willReturn($this->lisOutcomeRequestMock);

        $this->lisOutcomeRequestMock
            ->expects($this->once())
            ->method('getOperation')
            ->willReturn(null);

        $this->subject->parse($this->requestMock);
    }
}

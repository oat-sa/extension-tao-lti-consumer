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
use oat\taoLti\models\classes\Lis\LisAuthAdapter;
use oat\taoLti\models\classes\Lis\LisAuthAdapterException;
use oat\taoLti\models\classes\Lis\LisAuthAdapterFactory;
use oat\taoLti\models\classes\Lis\LtiProviderUser;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequest;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequestParser;
use oat\taoLtiConsumer\model\result\operations\replace\Service\Lti1p1ReplaceResultParser;
use Psr\Http\Message\ServerRequestInterface;
use tao_models_classes_UserException;

class Lti1p1ReplaceResultParserTest extends TestCase
{
    /** @var Lti1p1ReplaceResultParser */
    private $subject;

    /** @var LisOutcomeRequestParser|MockObject */
    private $lisOutcomeRequestParserMock;

    /** @var LisAuthAdapterFactory|MockObject */
    private $lisAuthAdapterFactoryMock;

    /** @var MockObject|ServerRequestInterface */
    private $requestMock;

    public function setUp(): void
    {
        $this->requestMock = $this->createMock(ServerRequestInterface::class);

        $this->subject = new Lti1p1ReplaceResultParser(
            $this->lisOutcomeRequestParserMock = $this->createMock(LisOutcomeRequestParser::class),
            $this->lisAuthAdapterFactoryMock = $this->createMock(LisAuthAdapterFactory::class)
        );
    }

    public function testParse(): void
    {
        $lisAuthAdapterMock = $this->createMock(LisAuthAdapter::class);
        $ltiProviderUserMock = $this->createMock(LtiProviderUser::class);
        $ltiProviderMock = $this->createMock(LtiProvider::class);
        $lisOutcomeRequest = $this->createMock(LisOutcomeRequest::class);

        $this->lisOutcomeRequestParserMock
            ->expects($this->once())
            ->method('parse')
            ->willReturn($lisOutcomeRequest);

        $this->requestMock
            ->expects($this->once())
            ->method('getBody')
            ->willReturn('xml string');

        $ltiProviderUserMock
            ->expects($this->once())
            ->method('getLtiProvider')
            ->willReturn($ltiProviderMock);

        $this->lisAuthAdapterFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($lisAuthAdapterMock);

        $lisAuthAdapterMock
            ->expects($this->once())
            ->method('authenticate')
            ->willReturn($ltiProviderUserMock);

        $result = $this->subject->parse($this->requestMock);
        $this->assertSame($ltiProviderMock, $result->getLtiProvider());
        $this->assertSame($lisOutcomeRequest, $result->getLisOutcomeRequest());
    }

    public function testLisAuthAdapterFactoryThrowError(): void
    {
        $this->expectException(tao_models_classes_UserException::class);

        $this->lisAuthAdapterFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willThrowException(new LisAuthAdapterException());

        $this->subject->parse($this->requestMock);
    }
}

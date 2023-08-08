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

namespace oat\taoLtiConsumer\model\result\operations\replace\Service;

use common_exception_NotFound;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\Security\AccessTokenRequestValidator;
use oat\taoLtiConsumer\model\ltiProvider\repository\DeliveryLtiProviderRepository;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequestParser;
use oat\taoLtiConsumer\model\result\operations\replace\ReplaceResultOperationRequest;
use oat\taoLtiConsumer\model\result\ParsingException;
use Psr\Http\Message\ServerRequestInterface;

class Lti1p3ReplaceResultParser implements ReplaceResultParserInterface
{
    /** @var LisOutcomeRequestParser */
    private  $lisOutcomeRequestParser;

    /** @var DeliveryLtiProviderRepository */
    private  $ltiProviderRepository;

    /** @var AccessTokenRequestValidator */
    private  $accessTokenRequestValidator;

    public function __construct(
        LisOutcomeRequestParser $lisOutcomeRequestParser,
        DeliveryLtiProviderRepository $ltiProviderRepository,
        AccessTokenRequestValidator $accessTokenRequestValidator
    ) {
        $this->accessTokenRequestValidator = $accessTokenRequestValidator;
        $this->ltiProviderRepository = $ltiProviderRepository;
        $this->lisOutcomeRequestParser = $lisOutcomeRequestParser;
    }

    /**
     * @throws ParsingException
     * @throws LtiException
     * @throws common_exception_NotFound
     */
    public function parse(ServerRequestInterface $request): ReplaceResultOperationRequest
    {
        $parsedPayload = $this->lisOutcomeRequestParser->parse((string)$request->getBody());

        if (!$parsedPayload->getOperation()) {
            throw new ParsingException('Lis request does not contain valid operation');
        }

        $ltiProvider = $this->ltiProviderRepository->searchBySourcedId(
            $parsedPayload->getOperation()->getSourcedId()
        );

        $this->accessTokenRequestValidator
            ->withLtiProvider($ltiProvider)
            ->withRole(ReplaceResultParserInterface::REPLACE_RESULT_ROLE)
            ->validate($request);

        return new ReplaceResultOperationRequest($parsedPayload, $ltiProvider);
    }
}

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

use common_user_auth_AuthFailedException;
use oat\oatbox\service\ConfigurableService;
use oat\taoLti\models\classes\Lis\LisAuthAdapterFactory;
use oat\taoLti\models\classes\Lis\LtiProviderUser;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequestParser;
use oat\taoLtiConsumer\model\result\operations\replace\ReplaceResultOperationRequest;
use oat\taoLtiConsumer\model\result\ParsingException;
use Psr\Http\Message\ServerRequestInterface;
use tao_models_classes_UserException;

class Lti1p1ReplaceResultParser implements ReplaceResultParserInterface
{
    /** @var LisOutcomeRequestParser */
    private $lisOutcomeRequestParser;

    /** @var LisAuthAdapterFactory */
    private $lisAuthAdapterFactory;

    public function __construct(
        LisOutcomeRequestParser $lisOutcomeRequestParser,
        LisAuthAdapterFactory $lisAuthAdapterFactory
    ) {
        $this->lisAuthAdapterFactory = $lisAuthAdapterFactory;
        $this->lisOutcomeRequestParser = $lisOutcomeRequestParser;
    }

    /**
     * @throws ParsingException
     * @throws tao_models_classes_UserException
     */
    public function parse(ServerRequestInterface $request): ReplaceResultOperationRequest
    {
        $user = $this->getAuthorizeUser($request);
        $ltiProvider = $user->getLtiProvider();

        $payload = (string)$request->getBody();

        return new ReplaceResultOperationRequest($this->lisOutcomeRequestParser->parse($payload), $ltiProvider);
    }

    /**
     * @throws tao_models_classes_UserException
     */
    private function getAuthorizeUser(ServerRequestInterface $request): LtiProviderUser
    {
        try {
            return $this->lisAuthAdapterFactory->create($request)->authenticate();
        } catch (common_user_auth_AuthFailedException $authFailedException) {
            throw new tao_models_classes_UserException($authFailedException->getMessage());
        }
    }
}

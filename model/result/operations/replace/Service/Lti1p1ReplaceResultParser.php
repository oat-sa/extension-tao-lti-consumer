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
use Psr\Http\Message\ServerRequestInterface;
use tao_models_classes_UserException;

class Lti1p1ReplaceResultParser extends ConfigurableService implements ReplaceResultParserInterface
{
    public function parse(ServerRequestInterface $request): ReplaceResultOperationRequest
    {
        $user = $this->authorizeUser($request);
        $ltiProvider = $user->getLtiProvider();

        $payload = (string)$request->getBody();
        $requestParser = $this->getRequestParser();

        return new ReplaceResultOperationRequest($requestParser->parse($payload), $ltiProvider);
    }

    private function authorizeUser(ServerRequestInterface $request): LtiProviderUser
    {
        try {
            return $this->getLisAuthAdapterFactory()->create($request)->authenticate();
        } catch (common_user_auth_AuthFailedException $authFailedException) {
            throw new tao_models_classes_UserException($authFailedException->getMessage());
        }
    }

    private function getRequestParser(): LisOutcomeRequestParser
    {
        return $this->getServiceLocator()->get(LisOutcomeRequestParser::class);
    }

    private function getLisAuthAdapterFactory(): LisAuthAdapterFactory
    {
        return $this->getServiceLocator()->get(LisAuthAdapterFactory::class);
    }
}
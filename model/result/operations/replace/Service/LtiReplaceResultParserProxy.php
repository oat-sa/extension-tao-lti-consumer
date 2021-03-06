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

use oat\oatbox\service\ConfigurableService;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\Security\MissingScopeException;
use oat\taoLtiConsumer\model\result\operations\replace\ReplaceResultOperationRequest;
use oat\taoLtiConsumer\model\result\ParsingException;
use Psr\Http\Message\ServerRequestInterface;
use tao_models_classes_UserException;

class LtiReplaceResultParserProxy extends ConfigurableService implements ReplaceResultParserInterface
{
    /**
     * @throws ParsingException
     * @throws tao_models_classes_UserException
     * @throws ParsingException
     * @throws LtiException
     * @throws MissingScopeException
     * @throws tao_models_classes_UserException
     */
    public function parse(ServerRequestInterface $request): ReplaceResultOperationRequest
    {
        if ($this->isLti1p3($request)) {
            return $this->getLti1p3ReplaceResultParser()->parse($request);
        }

        return $this->getLti1p1ReplaceResultParser()->parse($request);
    }

    private function isLti1p3(ServerRequestInterface $request): bool
    {
        return $request->hasHeader('authorization') &&
            strpos($request->getHeader('authorization')[0], 'Bearer') === 0;
    }

    private function getLti1p1ReplaceResultParser(): ReplaceResultParserInterface
    {
        return $this->getServiceLocator()->get(Lti1p1ReplaceResultParser::class);
    }

    private function getLti1p3ReplaceResultParser(): ReplaceResultParserInterface
    {
        return $this->getServiceLocator()->get(Lti1p3ReplaceResultParser::class);
    }
}

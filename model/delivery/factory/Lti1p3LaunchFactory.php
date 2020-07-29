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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA
 */

namespace oat\taoLtiConsumer\model\delivery\factory;

use OAT\Library\Lti1p3Core\Message\Claim\ContextClaim;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\session\SessionService;
use oat\oatbox\user\User;
use oat\taoLti\models\platform\builder\Lti1p3LaunchBuilder;
use oat\taoLti\models\platform\builder\LtiLaunchBuilderInterface;
use oat\taoLti\models\tool\launch\factory\LtiLaunchFactoryInterface;
use oat\taoLti\models\tool\launch\LtiLaunchInterface;
use oat\taoLti\models\tool\launch\LtiLaunchParams;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;

class Lti1p3LaunchFactory extends ConfigurableService implements LtiLaunchFactoryInterface
{
    public function create(LtiLaunchParams $params): LtiLaunchInterface
    {
        $builder = $this->getBuilder();

        //@TODO Anonymous user must be supported
        /** @var User $user */
        $user = $this->getServiceLocator()
            ->get(SessionService::SERVICE_ID)
            ->getCurrentUser();

        $ltiProvider = $this->getLtiProvider($params->getProviderId());

        // @TODO Missing add return UR / or callback URL...
        // @TODO Add necessary extra claims
        // @TODO Add necessary roles
        return $builder->withProvider($ltiProvider)
            ->withUser($user)
            ->withClaims(
                [
                    new ContextClaim('contextId'),  // LTI claim representing the context
                    'myCustomClaim' => 'myCustomValue' // custom claim
                ]
            )->withRoles(
                [
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner'
                ]
            )->build();
    }

    private function getBuilder(): LtiLaunchBuilderInterface
    {
        return $this->getServiceLocator()->get(Lti1p3LaunchBuilder::class);
    }

    private function getLtiProvider($id): LtiProvider
    {
        return $this->getServiceLocator()->get(LtiProviderService::class)->searchById($id);
    }
}

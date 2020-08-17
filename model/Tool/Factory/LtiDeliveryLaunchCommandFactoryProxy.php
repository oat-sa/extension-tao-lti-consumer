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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA
 */

declare(strict_types=1);

namespace oat\taoLtiConsumer\model\Tool\Factory;

use LogicException;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\Tool\Factory\LtiLaunchCommandFactoryInterface;
use oat\taoLti\models\classes\Tool\LtiLaunchCommandInterface;

class LtiDeliveryLaunchCommandFactoryProxy extends ConfigurableService implements LtiLaunchCommandFactoryInterface
{
    use OntologyAwareTrait;

    public function create(array $config): LtiLaunchCommandInterface
    {
        /** @var LtiProvider $ltiProvider */
        $ltiProvider = $config['ltiProvider'];

        if ($ltiProvider->getLtiVersion() === '1.1') {
            return $this->getLti1p1LaunchCommandFactory()->create($config);
        }

        if ($ltiProvider->getLtiVersion() === '1.3') {
            return $this->getLti1p3LaunchCommandFactory()->create($config);
        }

        throw new LogicException(
            sprintf(
                'LTI version %s is not supported',
                $ltiProvider->getLtiVersion()
            )
        );
    }

    private function getLti1p1LaunchCommandFactory(): LtiLaunchCommandFactoryInterface
    {
        return $this->getServiceLocator()->get(Lti1p1DeliveryLaunchCommandFactory::class);
    }

    private function getLti1p3LaunchCommandFactory(): LtiLaunchCommandFactoryInterface
    {
        return $this->getServiceLocator()->get(Lti1p3DeliveryLaunchCommandFactory::class);
    }
}

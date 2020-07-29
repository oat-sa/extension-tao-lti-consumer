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

use oat\oatbox\service\ConfigurableService;
use oat\taoLti\models\tool\launch\factory\LtiLaunchFactoryInterface;
use oat\taoLti\models\tool\launch\LtiLaunchInterface;
use oat\taoLti\models\tool\launch\LtiLaunchParams;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;
use oat\taoLtiConsumer\model\delivery\container\Lti1p1DeliveryLaunch;

class Lti1p3LaunchFactory extends ConfigurableService implements LtiLaunchFactoryInterface
{
    public function create(LtiLaunchParams $params): LtiLaunchInterface
    {
        return new Lti1p1DeliveryLaunch('', []);
    }

    private function getLtiProvider($id): LtiProvider
    {
        return $this->getServiceLocator()->get(LtiProviderService::class)->searchById($id);
    }
}

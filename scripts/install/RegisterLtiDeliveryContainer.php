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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoLtiConsumer\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\taoDelivery\model\container\delivery\DeliveryContainerRegistry;
use oat\taoLtiConsumer\model\delivery\container\LtiDeliveryContainer;

class RegisterLtiDeliveryContainer extends InstallAction
{
    public function __invoke($params)
    {
        $registry = DeliveryContainerRegistry::getRegistry();
        $registry->setServiceLocator($this->getServiceManager());
        $registry->registerContainerType('lti', new LtiDeliveryContainer());

        return \common_report_Report::createSuccess(__('LTI delivery container successfully registered.'));
    }
}

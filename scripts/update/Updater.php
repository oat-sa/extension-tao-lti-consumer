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
 */
namespace oat\taoLtiConsumer\scripts\update;

use common_Exception;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;
use oat\tao\model\user\TaoRoles;
use oat\taoDelivery\model\container\delivery\DeliveryContainerRegistry;
use oat\taoLtiConsumer\controller\ResultController;
use oat\taoLtiConsumer\model\BaseDeliveryExecutionGetter;
use oat\taoLtiConsumer\model\delivery\container\LtiDeliveryContainer;
use oat\taoLtiConsumer\model\DeliveryExecutionGetterInterface;

/**
 * taoLtiConsumer Updater.
 */
class Updater extends \common_ext_ExtensionUpdater
{
    /**
     * Perform update from $currentVersion to $versionUpdatedTo.
     *
     * @param string $initialVersion
     *
     * @return void
     * @throws common_Exception
     */
    public function update($initialVersion)
    {
        $this->skip('0.0.0', '0.0.1');

        if ($this->isVersion('0.0.1')) {
            $registry = DeliveryContainerRegistry::getRegistry();
            $registry->setServiceLocator($this->getServiceManager());
            $registry->registerContainerType('lti', new LtiDeliveryContainer());
            $this->setVersion('0.1.0');
        }

        $this->skip('0.1.0', '0.6.2');

        if ($this->isVersion('0.6.2')) {
            AclProxy::applyRule(
                new AccessRule(AccessRule::GRANT, TaoRoles::ANONYMOUS, ResultController::class)
            );
            $this->setVersion('1.0.0');
        }

        if ($this->isVersion('1.0.0')) {
            $baseDeGetter = new BaseDeliveryExecutionGetter();
            $this->getServiceManager()->register(DeliveryExecutionGetterInterface::SERVICE_ID, $baseDeGetter);
            $this->setVersion('1.1.0');
        }
    }
}

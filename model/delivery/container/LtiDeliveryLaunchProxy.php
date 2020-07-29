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

namespace oat\taoLtiConsumer\model\delivery\container;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoLti\models\tool\launch\LtiLaunchInterface;
use oat\taoLti\models\tool\launch\LtiLaunchParams;
use oat\taoLti\models\classes\LtiProvider\LtiProvider;
use oat\taoLtiConsumer\model\delivery\factory\Lti1p1LaunchFactory;
use oat\taoLtiConsumer\model\delivery\factory\Lti1p3LaunchFactory;

class LtiDeliveryLaunchProxy extends ConfigurableService
{
    use OntologyAwareTrait;

    public function launch(
        string $launchUrl,
        LtiProvider $ltiProvider,
        DeliveryExecution $execution
    ): LtiLaunchInterface
    {
        $launchParams = new LtiLaunchParams(
            $ltiProvider->getId(),
            $launchUrl,
            $execution->getIdentifier()
        );

        if ($ltiProvider->getLtiVersion() === '1.3') {
            return $this->getServiceLocator()->get(Lti1p3LaunchFactory::class)->create($launchParams);
        }

        if ($ltiProvider->getLtiVersion() === '1.1') {
            return $this->getServiceLocator()->get(Lti1p1LaunchFactory::class)->create($launchParams);
        }
    }
}

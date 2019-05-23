<?php
/**
 * Created by PhpStorm.
 * User: siwane
 * Date: 23/05/19
 * Time: 18:50
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
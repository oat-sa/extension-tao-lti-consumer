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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 */

namespace oat\taoLtiConsumer\controller;


use function GuzzleHttp\Psr7\build_query;
use oat\ltiDeliveryProvider\model\LTIDeliveryTool;
use oat\taoLti\models\classes\LaunchData\Validator\Lti11LaunchDataValidator;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLtiConsumer\model\LtiLaunchDataCreator;
use oat\taoLtiConsumer\scripts\install\RegisterTaoConsumer;
use tao_actions_CommonModule;

class LtiConsumer extends tao_actions_CommonModule
{
    public function launchToolProvider()
    {
        $deliveryId = \tao_helpers_Uri::decode($this->getRequestParameter('deliveryId'));

        $ltiUrl = LTIDeliveryTool::singleton()->getLaunchUrl(array('delivery' => $deliveryId));

        $ltiLaunchDataCreator = new LtiLaunchDataCreator();

        $params[LtiLaunchData::LTI_MESSAGE_TYPE]   = Lti11LaunchDataValidator::LTI_MESSAGE_TYPE;
        $params[LtiLaunchData::LTI_VERSION]        = 'LTI-1p3';
        $params[LtiLaunchData::RESOURCE_LINK_ID]   = 'ddd';
        $params[LtiLaunchData::OAUTH_CONSUMER_KEY] = $ltiLaunchDataCreator->getOauthConsumerKey(RegisterTaoConsumer::DEFAULT_LABEL);
        $params[LtiLaunchData::CONTEXT_ID]         = 'dsgfjkgjfdk';

        $ltiUrl .= '?';
        $ltiUrl .= build_query($params);

        //$this->redirect($ltiUrl);
        echo($ltiUrl);
    }
}
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


use oat\ltiDeliveryProvider\model\LTIDeliveryTool;
use oat\tao\model\oauth\OauthService;
use oat\taoLtiConsumer\model\LtiLaunchDataCreator;
use tao_actions_CommonModule;

class LtiConsumer extends tao_actions_CommonModule
{
    public function index()
    {

    }

    public function launchToolProvider()
    {
        $deliveryId = \tao_helpers_Uri::decode($this->getRequestParameter('deliveryId'));
        $ltiUrl = LTIDeliveryTool::singleton()->getLaunchUrl(array('delivery' => $deliveryId));

        $ltiLaunchDataCreator = new LtiLaunchDataCreator('ddd1', 'dsgfjkgjfdk1');

        /** @var OauthService $oauthService */
        $oauthService = $this->getServiceLocator()->get(OauthService::SERVICE_ID);
        $oauthRequest = $oauthService->signFromConsumerAndToken(
            $ltiLaunchDataCreator->getHttpRequest(),
            $ltiLaunchDataCreator->getOauthConsumer()
        );

        $this->setData('launchUrl', $oauthRequest->getUrl());
        $this->setData('ltiData', $oauthRequest->getParams());
        $this->setData('client_config_url', $this->getClientConfigUrl());
        $this->setView('ltiConsumer.tpl', 'taoLti');
    }

    public function stopToolProvider()
    {
        echo '<h1>Thank you!</h1>';
    }
}

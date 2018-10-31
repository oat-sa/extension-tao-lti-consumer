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

namespace oat\taoLtiConsumer\model;


use IMSGlobal\LTI\OAuth\OAuthConsumer;
use oat\taoLti\models\classes\LaunchData\Validator\Lti11LaunchDataValidator;
use oat\taoLti\models\classes\LtiLaunchData;

class LtiLaunchDataCreator
{
    const LTI_VERSION_1_3 = 'LTI-1p3';
    const ROLES_LEARNER = 'Learner';

    /** @var string */
    protected $resourceLinkId;

    /** @var string */
    protected $contextId;

    public function __construct($resourceLinkId, $contextId)
    {
        $this->setResourceLinkId($resourceLinkId);
        $this->setContextId($contextId);
    }

    public function getLtiMessageType()
    {
        return Lti11LaunchDataValidator::LTI_MESSAGE_TYPE;
    }

    public function getLtiVersion()
    {
        return static::LTI_VERSION_1_3;
    }

    public function getRoles()
    {
        return static::ROLES_LEARNER;
    }

    public function getToolProviderUrl()
    {
        return 'http://ionut.tao.cloud/ltiDeliveryProvider/DeliveryTool/launch/eyJkZWxpdmVyeSI6Imh0dHA6XC9cL3d3dy50YW90ZXN0aW5nLmNvbVwvb250b2xvZ2llc1wvaW9udXQucmRmI2kxNTMyNjEzODkxMjY2MTEwNyJ9';
        return '';
    }

    public function getOauthConsumerKey()
    {
        return 'key';
        return 'Zu57Qj33';
    }

    public function getOauthConsumerSecret()
    {
        return 'secret';
        return 'pNyGKUQAVkStI';
    }

    public function getUserUri()
    {
        return \common_session_SessionManager::getSession()->getUserUri();
    }

    /**
     * @param string $resourceLinkId
     */
    public function setResourceLinkId($resourceLinkId)
    {
        $this->resourceLinkId = $resourceLinkId;
    }

    /**
     * @return string
     */
    public function getResourceLinkId()
    {
        return $this->resourceLinkId;
    }

    /**
     * @param string $contextId
     */
    public function setContextId($contextId)
    {
        $this->contextId = $contextId;
    }

    /**
     * @return string
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * @return string
     */
    public function getReturnUrl()
    {
        return _url(
            'stopToolProvider',
            'LtiConsumer',
            'taoLtiConsumer',
            ['deliveryId' => 'aaaaa']
        );
    }

    /**
     * @return OAuthConsumer
     */
    public function getOauthConsumer()
    {
        return new OAuthConsumer(
            $this->getOauthConsumerKey(),
            $this->getOauthConsumerSecret()
        );
    }

    /**
     * @return \common_http_Request
     */
    public function getHttpRequest()
    {
        return new \common_http_Request(
            $this->getToolProviderUrl(),
            \common_http_Request::METHOD_POST,
            $this->getLtiParams()
        );
    }

    /**
     * @return array
     */
    public function getLtiParams()
    {
        return [
            LtiLaunchData::LTI_MESSAGE_TYPE               => $this->getLtiMessageType(),
            LtiLaunchData::LTI_VERSION                    => $this->getLtiVersion(),
            LtiLaunchData::OAUTH_CONSUMER_KEY             => $this->getOauthConsumerKey(),
            LtiLaunchData::RESOURCE_LINK_ID               => $this->getResourceLinkId(),
            LtiLaunchData::CONTEXT_ID                     => $this->getContextId(),
            LtiLaunchData::USER_ID                        => $this->getUserUri(),
            LtiLaunchData::ROLES                          => $this->getRoles(),
            LtiLaunchData::LAUNCH_PRESENTATION_RETURN_URL => $this->getReturnUrl(),
        ];
    }

}

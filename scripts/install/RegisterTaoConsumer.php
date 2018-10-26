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

namespace oat\taoLtiConsumer\scripts\install;


use oat\generis\model\OntologyRdfs;
use oat\oatbox\extension\InstallAction;
use oat\tao\model\oauth\DataStore;
use oat\taoLti\controller\RestService;
use oat\taoLti\models\classes\LtiRestApiService;

/**
 * This post-installation script creates the ContainerService
 */
class RegisterTaoConsumer extends InstallAction
{
    const DEFAULT_LABEL = 'Generic TAO Consumer';

    const DEFAULT_KEY_MIN_LENGTH = 8;
    const DEFAULT_KEY_MAX_LENGTH = 12;
    const DEFAULT_SECRET_MIN_LENGTH = 13;
    const DEFAULT_SECRET_MAX_LENGTH = 16;

    /**
     * Sets the ContainerService to TenantService.
     *
     * @param $params
     *
     * @return \common_report_Report
     */
    public function __invoke($params)
    {
        $service = LtiRestApiService::singleton();

        $properties = [];

        // Label
        if (!empty($params[0])) {
            $properties[OntologyRdfs::RDFS_LABEL] = $params[0];
        } else {
            $properties[OntologyRdfs::RDFS_LABEL] = self::DEFAULT_LABEL;
        }

        // OAuth Key
        if (!empty($params[1])) {
            $properties[DataStore::PROPERTY_OAUTH_KEY] = $params[1];
        } else {
            $properties[DataStore::PROPERTY_OAUTH_KEY] = \helpers_Random::generateString(
                rand(self::DEFAULT_KEY_MIN_LENGTH, self::DEFAULT_KEY_MAX_LENGTH)
            );
        }

        // OAuth Secret
        if (!empty($params[2])) {
            $properties[DataStore::PROPERTY_OAUTH_SECRET] = $params[2];
        } else {
            $properties[DataStore::PROPERTY_OAUTH_SECRET] = \helpers_Random::generateString(
                rand(self::DEFAULT_SECRET_MIN_LENGTH, self::DEFAULT_SECRET_MAX_LENGTH)
            );
        }

        // OAuth Callback
        if (!empty($params[3])) {
            $properties[DataStore::PROPERTY_OAUTH_CALLBACK] = $params[3];
        }

        // LTI User Id
        if (!empty($params[4])) {
            $properties[RestService::LTI_USER_ID] = $params[4];
        }

        // LTI Consumer Key
        if (!empty($params[5])) {
            $properties[RestService::LTI_CONSUMER_KEY] = $params[5];
        }

        $service->createFromArray($properties);

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'Tao Consumer registered!');
    }
}

<?php
/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2019 (original work) Open Assessment Technologies SA
 *
 *
 */

namespace oat\taoLtiConsumer\model\credentials;


use common_exception_InvalidArgumentType;
use core_kernel_classes_Resource;
use oat\generis\model\OntologyAwareTrait;
use oat\tao\model\oauth\DataStore;

class RdfCredentialsProvider implements CredentialsProviderInterface
{

    use OntologyAwareTrait;
    /**
     * @var core_kernel_classes_Resource
     */
    private $storage;
    /**
     * @var string
     */
    private $key;
    /**
     * @var string
     */
    private $secret;


    /**
     * RdfProvider constructor.
     * @param string $id
     * @throws common_exception_InvalidArgumentType
     */
    public function __construct($id)
    {
        $this->storage = $this->getResource($id);
        $credentials = $this->storage->getPropertiesValues([
            DataStore::PROPERTY_OAUTH_KEY,
            DataStore::PROPERTY_OAUTH_SECRET,
        ]);
        $this->key = (string)reset($credentials[DataStore::PROPERTY_OAUTH_KEY]);
        $this->secret = (string)reset($credentials[DataStore::PROPERTY_OAUTH_SECRET]);
    }

    /**
     * @return string
     */
    public function getConsumerKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getConsumerSecret()
    {
        return $this->secret;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->storage->getLabel();
    }

    public function getId()
    {
        return $this->storage->getUri();
    }

    public function getOwnClass()
    {
        return __CLASS__;
    }
}
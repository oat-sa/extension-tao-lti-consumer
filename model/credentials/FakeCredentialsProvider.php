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

/**
 * Class FakeCredentialsProvider
 * @package oat\taoLtiConsumer\model\credentials
 */
class FakeCredentialsProvider implements CredentialsProviderInterface
{

    /**
     * @return string
     */
    public function getConsumerKey()
    {
        return 'fake_key';
    }

    /**
     * @return string
     */
    public function getConsumerSecret()
    {
        return 'fake_secret';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'fake_label';
    }

    public function getId()
    {
        return 'fake_id';
    }

    public function getOwnClass()
    {
        return __CLASS__;
    }
}

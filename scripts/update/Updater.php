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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoLtiConsumer\scripts\update;


/**
 * TAO Premium Edition Updater.
 */
class Updater extends \common_ext_ExtensionUpdater
{
    /**
     * Perform update from $currentVersion to $versionUpdatedTo.
     *
     * @param string $currentVersion
     * @return string $versionUpdatedTo
     *
     * @throws \common_Exception
     */
    public function update($initialVersion)
    {
        $this->skip('0.0.0', '0.0.1');
    }
}

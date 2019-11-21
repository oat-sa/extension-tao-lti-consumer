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

namespace oat\taoLtiConsumer\model\result\operations\failure;

use oat\oatbox\service\ConfigurableService;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseInterface;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseSerializer;
use oat\taoLtiConsumer\model\result\operations\ResponseSerializerInterface;

class BasicResponseSerializer extends ConfigurableService implements ResponseSerializerInterface
{
    /**
     * Serializes response without body response node
     * @param LisOutcomeResponseInterface $response
     * @return string
     */
    public function toXml(LisOutcomeResponseInterface $response)
    {
        return $this
            ->getLisOutcomeResponseSerializer()
            ->createXmlElement($response, null)
            ->asXML();
    }

    /**
     * @return LisOutcomeResponseSerializer
     */
    protected function getLisOutcomeResponseSerializer()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(LisOutcomeResponseSerializer::class);
    }
}

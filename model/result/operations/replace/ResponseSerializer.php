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

namespace oat\taoLtiConsumer\model\result\operations\replace;

use oat\oatbox\service\ConfigurableService;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseInterface;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseSerializer;
use oat\taoLtiConsumer\model\result\operations\ResponseSerializerInterface;
use SimpleXMLElement;

class ResponseSerializer extends ConfigurableService implements ResponseSerializerInterface
{
    public const BODY_RESPONSE_ELEMENT_NAME = 'replaceResultResponse';

    /**
     * @param LisOutcomeResponseInterface|Response $response
     * @return string
     */
    public function toXml(LisOutcomeResponseInterface $response)
    {
        $bodyResponseNode = new SimpleXMLElement(sprintf('<%s />', self::BODY_RESPONSE_ELEMENT_NAME));

        return $this
            ->getLisOutcomeResponseSerializer()
            ->createXmlElement($response, $bodyResponseNode)
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

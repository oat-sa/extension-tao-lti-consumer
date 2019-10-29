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

namespace oat\taoLtiConsumer\model\result\operations;

use oat\oatbox\service\ConfigurableService;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseInterface;
use SimpleXMLElement;

class BasicResponseSerializer extends ConfigurableService implements ResponseSerializerInterface
{
    /**
     * Serializes response without body response node
     * @param LisOutcomeResponseInterface $response
     * @return string
     */
    public function toXml(LisOutcomeResponseInterface $response)
    {
        return $this->createMainNode($response, null)->asXML();
    }

    /**
     * @param LisOutcomeResponseInterface $response
     * @param SimpleXMLElement|null $bodyResponseNode
     * @return SimpleXMLElement
     */
    protected function createMainNode(LisOutcomeResponseInterface $response, $bodyResponseNode)
    {
        $root = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<imsx_POXEnvelopeResponse xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0" />'
        );

        $header = $root->addChild('imsx_POXHeader')->addChild('imsx_POXResponseHeaderInfo');
        $header->addChild('imsx_version', 'V1.0');
        $header->addChild('imsx_messageIdentifier', $response->getMessageIdentifier());

        $statusInfo = $header->addChild('imsx_statusInfo');
        $statusInfo->addChild('imsx_codeMajor', $response->getCodeMajor());
        $statusInfo->addChild('imsx_severity', 'status');
        $statusInfo->addChild('imsx_description', $response->getStatusDescription());
        $statusInfo->addChild('imsx_messageRefIdentifier', $response->getMessageRefIdentifier());

        if ($response->getOperationRefIdentifier() !== null) {
            $statusInfo->addChild('imsx_operationRefIdentifier', $response->getOperationRefIdentifier());
        }
        $body = $root->addChild('imsx_POXBody');

        if ($bodyResponseNode !== null) {
            self::appendChild($body, $bodyResponseNode);
        }

        return $root;
    }

    protected static function appendChild(SimpleXMLElement $to, SimpleXMLElement $from)
    {
        $toDom = dom_import_simplexml($to);
        $fromDom = dom_import_simplexml($from);
        $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
    }
}

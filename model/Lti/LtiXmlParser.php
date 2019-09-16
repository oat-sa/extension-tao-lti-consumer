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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA
 */

namespace oat\taoLtiConsumer\model\Lti;

use common_exception_InvalidArgumentType;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManagerAwareTrait;
use oat\oatbox\log\LoggerAwareTrait;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoLti\models\classes\LtiException;
use oat\taoLtiConsumer\model\ResultException;
use taoResultServer_models_classes_OutcomeVariable as OutcomeVariable;

/**
 * Class ResultService
 * Class to manage XML result data with score and to store it in DeliveryExecution
 * @package oat\taoLtiConsumer\model\classes
 */
class LtiXmlParser
{
    const TYPE_REPLACE_RESULT = 'replaceResult';
    const TYPE_READ_RESULT = 'readResult';
    const TYPE_DELETE_RESULT = 'deleteResult';
    const REQUEST_TYPES = [
        self::TYPE_REPLACE_RESULT,
        self::TYPE_READ_RESULT,
        self::TYPE_DELETE_RESULT,
    ];

    /**
     * @param string $payload
     * @return string
     * @throws LtiException when the payload is not valid.
     */
    public function getRequestType($payload)
    {
        $dom = new \DOMDocument();
        $dom->loadXML($payload);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('lti', "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0");
        $elements = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/*');

        // No child or more than one children are wrong requests.
        if (count($elements) !== 1) {
            throw new LtiException('Request type not found', self::ERROR_WRONG_REQUEST_TYPE);
        }
        $requestElement = array_shift($elements);
        if (!$requestElement instanceof \DOMNode) {
            throw new LtiException('Xml payload is not valid', self::ERROR_WRONG_REQUEST_TYPE);
        }
        if (!isset(self::REQUEST_TYPES[$requestElement->nodeName])) {
            throw new LtiException('Unknown request type ' . $requestElement->nodeName, self::ERROR_WRONG_REQUEST_TYPE);
        }

        return self::REQUEST_TYPES[$requestElement->nodeName];
    }
}

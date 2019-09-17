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

namespace oat\taoLtiConsumer\model\result\parser;

use DOMDocument;
use DOMXPath;
use oat\taoLtiConsumer\model\result\parser\dataExtractor\DataExtractor;
use oat\taoLtiConsumer\model\result\parser\dataExtractor\ReplaceResultDataExtractor;
use oat\taoLtiConsumer\model\result\ResultException;
use oat\taoLtiConsumer\test\unit\model\result\parser\ParserException;

/**
 * Class ResultService
 * Class to manage XML result data with score and to store it in DeliveryExecution
 * @package oat\taoLtiConsumer\model\classes
 */
class XmlResultParser
{
    protected $requestType;
    protected $data;

    public function parse($xml)
    {
        $xpath = $this->load($xml);
        $dataExtractor = $this->getDataExtractor($xpath);

        $this->requestType = $dataExtractor->getRequestType();
        $this->data = $dataExtractor->getData($xpath);

        $this->close($xpath);
        return $this;
    }

    public function getRequestType()
    {
        return $this->requestType;
    }

    public function getData()
    {
        return $this->data;
    }

    protected function load($xml)
    {
        if (!is_string($xml)) {
            throw new \InvalidArgumentException();
        }

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('lti', "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0");

        return $xpath;
    }

    protected function getDataExtractor($xpath)
    {
        $elements = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/*');
        if (count($elements) !== 1) {
            throw new ParserException('Xml payload is not valid', 500);
        }

        foreach ($this->getDataExtractors() as $extractor) {
            if ($extractor->accepts($xpath)) {
                 return $extractor;
            }
        }

        throw new ResultException(
//            self::$statuses[self::STATUS_METHOD_NOT_IMPLEMENTED],
//            self::STATUS_METHOD_NOT_IMPLEMENTED,
//            null,
//            [
//                self::TEMPLATE_VAR_CODE_MAJOR => self::FAILURE_MESSAGE,
//                self::TEMPLATE_VAR_DESCRIPTION => self::$statuses[self::STATUS_METHOD_NOT_IMPLEMENTED],
//                self::TEMPLATE_VAR_MESSAGE_ID => '',
//                self::TEMPLATE_VAR_MESSAGE_REF_IDENTIFIER => '',
//            ]
        );
    }

    /**
     * @return DataExtractor[]
     */
    protected function getDataExtractors()
    {
        return [
            new ReplaceResultDataExtractor()
        ];
    }

    protected function close($xpath)
    {
        unset($xpath);
    }
}

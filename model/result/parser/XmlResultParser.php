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
use oat\oatbox\service\ConfigurableService;
use oat\taoLtiConsumer\model\result\parser\dataExtractor\DataExtractor;
use oat\taoLtiConsumer\model\result\ResultException;
use oat\taoLtiConsumer\model\result\MessageBuilder;

/**
 * Class XmlResultParser
 * Class to parse XML result data
 *
 * @author Moyon Camille
 */
class XmlResultParser extends ConfigurableService
{
    const SERVICE_ID = 'taoLtiConsumer/xmlResultParser';
    const OPTION_DATA_EXTRACTORS = 'extractors';

    protected $requestType;
    protected $data;

    /**
     * Parse $xml to extract data based on configured extractors
     *
     * @param $xml
     * @return $this
     * @throws ResultException
     */
    public function parse($xml)
    {
        $xpath = $this->load($xml);
        $dataExtractor = $this->getDataExtractor($xpath);

        $this->requestType = $dataExtractor->getRequestType();
        $this->data = $dataExtractor->getData($xpath);

        $this->close($xpath);
        return $this;
    }

    /**
     * Get the request type associated to xml
     *
     * @return string
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * Get data extracted from xml
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Load xml to xpath object, register lti result namespace
     *
     * @param $xml
     * @return DOMXPath
     * @throws ResultException
     */
    protected function load($xml)
    {
        if (!is_string($xml)) {
            throw ResultException::fromCode();
        }

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('lti', "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0");

        return $xpath;
    }

    /**
     * Give an applicable dataExtractor that accept incoming $xpath
     *
     * @param $xpath
     * @return DataExtractor
     * @throws ResultException
     */
    protected function getDataExtractor($xpath)
    {
        $elements = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/*');
        if (count($elements) !== 1) {
            throw ResultException::fromCode();
        }

        foreach ($this->getDataExtractors() as $extractor) {
            if ($extractor->accepts($xpath)) {
                 return $extractor;
            }
        }

        throw ResultException::fromCode(MessageBuilder::STATUS_METHOD_NOT_IMPLEMENTED);
    }

    /**
     * Get configured dataExtractor
     *
     * @return DataExtractor[]
     */
    protected function getDataExtractors()
    {
        $extractors = [];
        $configuredExtractors = $this->getOption(self::OPTION_DATA_EXTRACTORS);
        if (is_array($configuredExtractors)) {
            foreach ($configuredExtractors as $configuredExtractor) {
                if ($configuredExtractor instanceof DataExtractor) {
                    $extractors[] = $configuredExtractor;
                }
            }
        }
        return $extractors;
    }

    /**
     * Destroy xpath instance
     *
     * @param $xpath
     */
    protected function close($xpath)
    {
        unset($xpath);
    }
}

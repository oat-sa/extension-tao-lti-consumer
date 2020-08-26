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

namespace oat\taoLtiConsumer\model\result\messages;

use DOMDocument;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use InvalidArgumentException;
use LogicException;
use oat\oatbox\service\ConfigurableService;
use oat\taoLtiConsumer\model\result\operations\OperationsCollection;
use oat\taoLtiConsumer\model\result\ParsingException;

class LisOutcomeRequestParser extends ConfigurableService
{
    protected const XML_NAMESPACE = 'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0';
    protected const XML_NAMESPACE_PREFIX = 'lti';

    /**
     * @throws ParsingException
     */
    public function parse(string $xml): LisOutcomeRequest
    {
        $xpath = $this->getXpath($xml);
        $messageIdentifier = $this->getMessageIdentifier($xpath);
        try {
            $operationNode = $this->getOperationNode($xpath);
            $operationName = $operationNode->nodeName;
            $operationParser = $this->getOperationsCollection()->getOperationRequestParser($operationName);
            $operationRequest = $operationParser
                ? $operationParser->parse($xpath, self::XML_NAMESPACE_PREFIX, $operationNode)
                : null;
        } catch (ParsingException $parsingException) {
            throw new ParsingException(
                $parsingException->getMessage(),
                $parsingException->getCode(),
                $messageIdentifier,
                $parsingException
            );
        }

        return new LisOutcomeRequest($messageIdentifier, $operationName, $operationRequest);
    }

    /**
     * @param $xml
     * @return DOMXPath
     * @throws ParsingException
     */
    protected function getXpath($xml)
    {
        if (!is_string($xml)) {
            throw new InvalidArgumentException('xml argument is not a string');
        }

        // prevent libxml from writing warnings to the output
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        if (!$dom->loadXML($xml)) {
            // free memory
            libxml_clear_errors();
            throw new ParsingException('passed string is not a valid XML');
        }

        $xpath = new DOMXPath($dom);
        if (!$xpath->registerNamespace(self::XML_NAMESPACE_PREFIX, self::XML_NAMESPACE)) {
            // it's internal error which doesn't depend on XML input and
            // appears only in case of invalid namespace/prefix passed
            throw new LogicException(sprintf(
                'Error registering "%s" namespace with "%s" prefix',
                self::XML_NAMESPACE,
                self::XML_NAMESPACE_PREFIX
            ));
        }

        return $xpath;
    }

    /**
     * @param DOMXPath $xpath
     * @return string
     * @throws ParsingException
     */
    protected function getMessageIdentifier(DOMXPath $xpath)
    {
        /** @var DOMNodeList $nodes */
        $nodes = $xpath->evaluate(sprintf(
            '/%1$s:imsx_POXEnvelopeRequest/%1$s:imsx_POXHeader/%1$s:imsx_POXRequestHeaderInfo/%1$s:imsx_messageIdentifier',
            self::XML_NAMESPACE_PREFIX
        ));
        if ($nodes->length !== 1) {
            throw new ParsingException("can't extract messageIdentifier");
        }
        return $nodes->item(0)->nodeValue;
    }

    /**
     * @param DOMXPath $xpath
     * @return DOMNode
     * @throws ParsingException
     */
    protected function getOperationNode(DOMXPath $xpath)
    {
        /** @var DOMNodeList $nodes */
        $nodes = $xpath->evaluate(
            sprintf('/%1$s:imsx_POXEnvelopeRequest/%1$s:imsx_POXBody/*', self::XML_NAMESPACE_PREFIX)
        );
        if ($nodes->length === 0) {
            throw new ParsingException('operation request tag not found');
        }
        if ($nodes->length > 1) {
            throw new ParsingException('multiple operation request tags not supported');
        }
        return $nodes->item(0);
    }

    protected function getOperationsCollection(): OperationsCollection
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(OperationsCollection::class);
    }
}

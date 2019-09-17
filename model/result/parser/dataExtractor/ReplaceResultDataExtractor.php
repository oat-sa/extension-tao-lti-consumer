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

namespace oat\taoLtiConsumer\model\result\parser\dataExtractor;

use DOMXPath;
use oat\taoLtiConsumer\model\result\parser\ParserException;

/**
 * Class ReplaceResultDataExtractor
 *
 * @author Camille Moyon
 * @package oat\taoLtiConsumer\model\result\parser\dataExtractor
 *
 */
class ReplaceResultDataExtractor implements DataExtractor
{
    const REQUEST_TYPE = 'replaceResult';

    protected $accepted = false;

    /**
     * Evaluate incoming $xpath to accept it for extraction
     *
     * Check if the body contains only one "replaceResultRequest" node
     *
     * @param DOMXPath $xpath
     * @return bool
     */
    public function accepts(DOMXPath $xpath)
    {
        return $this->accepted = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/lti:replaceResultRequest')->length === 1;
    }

    /**
     * Get the Request Type of current extractor e.g. replaceResult
     *
     * @return string
     */
    public function getRequestType()
    {
        return self::REQUEST_TYPE;
    }

    public function getData(DOMXPath $xpath)
    {
        if (!$this->accepted) {
            throw new ParserException();
        }

        $messageIdentifierNode = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXHeader/lti:imsx_POXRequestHeaderInfo/lti:imsx_messageIdentifier');
        if ($messageIdentifierNode->length != 1) {
            throw new ParserException('Xml payload do not contain valid "messageIdentifier".');
        }

        $scoreNode = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/lti:replaceResultRequest/lti:resultRecord/lti:result/lti:resultScore/lti:textString');
        if ($scoreNode->length != 1) {
            throw new ParserException('Xml payload do not contain valid "resultScore".');
        }

        $sourcedIdNode = $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/lti:replaceResultRequest/lti:resultRecord/lti:sourcedGUID/lti:sourcedId');
        if ($sourcedIdNode->length != 1) {
            throw new ParserException('Xml payload do not contain valid "sourcedId".');
        }

        return [
            'messageIdentifier' => $messageIdentifierNode->item(0)->nodeValue,
            'score' => $scoreNode->item(0)->nodeValue,
            'sourcedId' => $sourcedIdNode->item(0)->nodeValue,
        ];
    }
}
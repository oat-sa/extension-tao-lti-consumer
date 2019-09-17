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

class ReplaceResultDataExtractor implements DataExtractor
{
    const REQUEST_TYPE = 'replaceResult';

    public function accepts(DOMXPath $xpath)
    {
        return $xpath->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/lti:replaceResultRequest')->length > 0;
    }

    public function getRequestType()
    {
        return self::REQUEST_TYPE;
    }

    public function getData(DOMXPath $xpath)
    {
        $messageIdentifier = $xpath
            ->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXHeader/lti:imsx_POXRequestHeaderInfo/lti:imsx_messageIdentifier')
            ->item(0)
            ->nodeValue;

        $score = $xpath
            ->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/lti:replaceResultRequest/lti:resultRecord/lti:result/lti:resultScore/lti:textString')
            ->item(0)
            ->nodeValue;

        $sourcedId = $xpath
            ->evaluate('/lti:imsx_POXEnvelopeRequest/lti:imsx_POXBody/lti:replaceResultRequest/lti:resultRecord/lti:sourcedGUID/lti:sourcedId')
            ->item(0)
            ->nodeValue;

        return [
            'messageIdentifier' => $messageIdentifier,
            'score' => $score,
            'sourcedId' => $sourcedId,
        ];
    }
}
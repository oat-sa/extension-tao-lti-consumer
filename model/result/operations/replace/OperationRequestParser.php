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

use DOMNode;
use DOMXPath;
use oat\taoLtiConsumer\model\result\operations\BasicOperationRequestParser;
use oat\taoLtiConsumer\model\result\operations\OperationRequestParserInterface;
use oat\taoLtiConsumer\model\result\ParsingException;

class OperationRequestParser extends BasicOperationRequestParser implements OperationRequestParserInterface
{
    public const SCORE_MIN = 0;
    public const SCORE_MAX = 1;

    /**
     * @param DOMXPath $xpath
     * @param string $nsPrefix
     * @param DOMNode $operationNode
     * @return OperationRequest
     * @throws ParsingException
     */
    public function parse(DOMXPath $xpath, $nsPrefix, DOMNode $operationNode)
    {
        $sourceId = $this->getSourceId($xpath, $nsPrefix, $operationNode);
        if ($sourceId === null) {
            throw new ParsingException('sourceId not found');
        }

        $score = $this->getScore($xpath, $nsPrefix, $operationNode);
        if ($score === null) {
            throw new ParsingException('score not found');
        }

        if (!$this->isValidScore($score)) {
            throw new ParsingException(sprintf(
                'invalid score value: "%s", should be between %d and %d',
                $score,
                self::SCORE_MIN,
                self::SCORE_MAX
            ));
        }

        return new OperationRequest($sourceId, $score);
    }

    /**
     * @param DOMXPath $xpath
     * @param string $nsPrefix
     * @param DOMNode $operationNode
     * @return string|null
     */
    protected function getScore(DOMXPath $xpath, $nsPrefix, DOMNode $operationNode)
    {
        return $this->getSingleNodeValue(
            $xpath,
            $operationNode,
            sprintf('./%1$s:resultRecord/%1$s:result/%1$s:resultScore/%1$s:textString', $nsPrefix)
        );
    }

    /**
     * @param string|int|float $score
     * @return bool
     */
    protected function isValidScore($score)
    {
        return is_numeric($score) && $score >= self::SCORE_MIN && $score <= self::SCORE_MAX;
    }
}

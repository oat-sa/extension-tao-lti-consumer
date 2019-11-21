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
namespace oat\taoLtiConsumer\test\unit\model\result\messages;

use DOMDocument;
use DOMXPath;
use oat\generis\test\TestCase;
use oat\taoLtiConsumer\model\result\operations\replace\OperationRequestParser;
use oat\taoLtiConsumer\model\result\ParsingException;

class OperationRequestParserTest extends TestCase
{
    public function testParseValid()
    {
        $parser = new OperationRequestParser();
        /** @noinspection PhpUnhandledExceptionInspection */
        $result = $parser->parse(...$this->getParseArgs($this->getTestMessageXml('src_id', '0.3')));
        $this->assertEquals('0.3', $result->getScore());
        $this->assertEquals('src_id', $result->getSourcedId());
    }

    public function testParseScoreRange()
    {
        $parser = new OperationRequestParser();

        /** @noinspection PhpUnhandledExceptionInspection */
        $result = $parser->parse(...$this->getParseArgs($this->getTestMessageXml('src_id', '0')));
        $this->assertEquals('0', $result->getScore());

        /** @noinspection PhpUnhandledExceptionInspection */
        $result = $parser->parse(...$this->getParseArgs($this->getTestMessageXml('src_id', '1')));
        $this->assertEquals('1', $result->getScore());

        $this->expectException(ParsingException::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        $parser->parse(...$this->getParseArgs($this->getTestMessageXml('src_id', '1.4')));
    }

    public function testParseMissingSrcIdNode()
    {
        $parser = new OperationRequestParser();
        $this->expectException(ParsingException::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        $parser->parse(...$this->getParseArgs($this->getTestMessageXml(null, '0.3')));
    }

    public function testParseMissingScoreNode()
    {
        $parser = new OperationRequestParser();
        $this->expectException(ParsingException::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        $parser->parse(...$this->getParseArgs($this->getTestMessageXml('src_id', null)));
    }

    protected function getParseArgs($xml)
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('pr', 'http://namespace_example');
        $opNode = $xpath->evaluate(sprintf('/pr:imsx_POXEnvelopeRequest/pr:imsx_POXBody/*'))->item(0);
        return [$xpath, 'pr', $opNode];
    }

    /**
     * @param string|null $sourceId
     * @param string|null $score
     * @return string
     */
    protected function getTestMessageXml($sourceId, $score)
    {
        $sourceNodeXml = $sourceId !== null
            ? "<sourcedGUID><sourcedId>$sourceId</sourcedId></sourcedGUID>"
            : '';

        $scoreNodeXml = $score !== null
            ? "<resultScore><language>en-US</language><textString>$score</textString></resultScore>"
            : '';

        return '<?xml version = "1.0" encoding = "UTF-8"?>
            <imsx_POXEnvelopeRequest xmlns = "http://namespace_example">            
              <imsx_POXBody>
                <replaceResultRequest>
                  <resultRecord>
                   ' . $sourceNodeXml . '
                    <result>
                      ' . $scoreNodeXml . '
                    </result>
                  </resultRecord>
                </replaceResultRequest>
              </imsx_POXBody>
            </imsx_POXEnvelopeRequest>';
    }
}

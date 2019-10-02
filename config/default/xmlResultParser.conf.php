<?php

use oat\taoLtiConsumer\model\result\parser\dataExtractor\ReplaceResultDataExtractorInterface;
use oat\taoLtiConsumer\model\result\parser\XmlResultParser;

return new XmlResultParser([
    XmlResultParser::OPTION_DATA_EXTRACTORS => [
        new ReplaceResultDataExtractorInterface()
    ]
]);
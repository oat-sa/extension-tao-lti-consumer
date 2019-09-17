<?php

use oat\taoLtiConsumer\model\result\parser\dataExtractor\ReplaceResultDataExtractor;
use oat\taoLtiConsumer\model\result\parser\XmlResultParser;

return new XmlResultParser([
    XmlResultParser::OPTION_DATA_EXTRACTORS => [
        new ReplaceResultDataExtractor()
    ]
]);
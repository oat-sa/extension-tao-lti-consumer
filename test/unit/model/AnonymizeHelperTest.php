<?php
/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2019 (original work) Open Assessment Technologies SA
 */

namespace oat\taoLtiConsumer\test\unit\model\delivery\task;

use oat\generis\test\TestCase;
use oat\taoLtiConsumer\model\AnonymizeHelper;

class AnonymizeHelperTest extends TestCase
{

    /**
     * @dataProvider paramsToTest
     * @param array $inputDataSample
     * @param array $blackList
     * @param array $filteredDataSample
     */
    public function testAnonymize($inputDataSample, $blackList, $filteredDataSample)
    {
        $anonimizer = new AnonymizeHelper([AnonymizeHelper::OPTION_BLACK_LIST => $blackList]);
        $this->assertEquals($filteredDataSample, $anonimizer->anonymize($inputDataSample));
    }

    public function paramsToTest()
    {
        return [
            [['content' => '123'], ['content'], ['content' => '****']],
            [['deeper' => ['content' => '123']], ['content'], ['deeper' => ['content' => '****']]],
            [['mixed' => '345', 'deeper' => ['content' => '123']], ['content'], ['mixed' => '345', 'deeper' => ['content' => '****']]],
            [['mixed' => '345', 'another' => 'xxx', 'deeper' => ['content' => '123']], ['content', 'another'], ['mixed' => '345', 'another' => '****', 'deeper' => ['content' => '****']]],
            [json_encode(['mixed' => '345', 'another' => 'xxx', 'deeper' => ['content' => '123']]), ['content', 'another'], ['mixed' => '345', 'another' => '****', 'deeper' => ['content' => '****']]],
        ];
    }
}

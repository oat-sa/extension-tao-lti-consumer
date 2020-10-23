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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies SA
 */

declare(strict_types=1);

namespace oat\taoLtiConsumer\test\unit\model\Tool\Factory;

use oat\generis\test\TestCase;
use oat\tao\helpers\UrlHelper;
use oat\taoLtiConsumer\model\Tool\Factory\LisOutcomeServiceUrlFactory;
use PHPUnit\Framework\MockObject\MockObject;

class LisOutcomeServiceUrlFactoryTest extends TestCase
{
    /** @var UrlHelper|MockObject */
    private $urlHelper;

    /** @var LisOutcomeServiceUrlFactory */
    private $subject;

    public function setUp(): void
    {
        $this->urlHelper = $this->createMock(UrlHelper::class);
        $this->subject = new LisOutcomeServiceUrlFactory();
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    UrlHelper::class => $this->urlHelper,
                ]
            )
        );
    }

    public function testCreate(): void
    {
        $this->urlHelper
            ->method('buildUrl')
            ->with(
                'manageResults',
                'ResultController',
                'taoLtiConsumer'
            )
            ->willReturn('outcomeServiceUrl');

        $this->assertEquals('outcomeServiceUrl', $this->subject->create());
    }
}

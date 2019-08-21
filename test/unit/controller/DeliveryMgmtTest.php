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
 *
 */

namespace oat\taoLtiConsumer\test\unit\model\delivery\container;

use oat\generis\test\TestCase;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;
use oat\taoLtiConsumer\controller\DeliveryMgmt;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DeliveryMgmtTest extends TestCase
{
    /**
     * @dataProvider queryStringsToTest
     *
     * @param $queryString
     */
    public function testGetLtiProvidersFromLtiProviderService($queryString)
    {
        $providers = ['whatever1', 'whatever2'];

        /** @var LtiProviderService|MockObject $ltiProviderService */
        $ltiProviderService = $this->getMockBuilder(LtiProviderService::class)
            ->disableOriginalConstructor()
            ->setMethods(['findAll', 'searchByLabel'])
            ->getMockForAbstractClass();
        $ltiProviderService->method('findAll')->willReturn($providers);
        $ltiProviderService->method('searchByLabel')->with(trim($queryString))->willReturn($providers);

        /** @var ServiceLocatorInterface|MockObject $serviceLocator */
        $serviceLocator = $this->getMockBuilder(ServiceLocatorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        $serviceLocator->method('get')->with(LtiProviderService::class)->willReturn($ltiProviderService);

        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQueryParams'])
            ->getMockForAbstractClass();
        $request->method('getQueryParams')->willReturn($queryString);

        $subject = new DeliveryMgmt();
        $subject->setServiceLocator($serviceLocator);
        $subject->setRequest($request);

        $expected = ['total' => count($providers), 'items' => $providers];

        $this->assertEquals($expected, $subject->getLtiProvidersFromLtiProviderService());
    }

    public function queryStringsToTest()
    {
        return [
            [''],
            ['blah'],
        ];
    }
}

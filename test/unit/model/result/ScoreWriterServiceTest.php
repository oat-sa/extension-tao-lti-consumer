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

namespace oat\taoLtiConsumer\test\unit\model\result\parser;

use common_exception_Error;
use oat\generis\test\TestCase;
use oat\oatbox\event\EventManager;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoLtiConsumer\model\result\InvalidScoreException;
use oat\taoLtiConsumer\model\result\MessageBuilder;
use oat\taoLtiConsumer\model\result\ResultException;
use oat\taoLtiConsumer\model\result\ScoreWriterService;
use oat\taoLtiConsumer\model\result\XmlFormatterService;
use oat\taoResultServer\models\classes\ResultServerService;
use oat\taoResultServer\models\Exceptions\DuplicateVariableException;
use Zend\ServiceManager\ServiceLocatorInterface;
use \taoResultServer_models_classes_WritableResultStorage as WritableResultStorage;


class ScoreWriterServiceTest extends TestCase
{
    private $deliveryExecutionId = '3124567';
    private $sourcedId = '3124567';

    /**
     * Set of input args for tests
     * @return array
     */
    public function queryScoresToTest()
    {
        return [
            ['-1'],
            ['string'],
            ['2'],
            [null],
        ];
    }

    /**
     * @dataProvider queryScoresToTest
     *
     * @param $score
     * @throws common_exception_Error
     * @throws InvalidServiceManagerException
     * @throws ResultException
     * @throws DuplicateVariableException
     */
    public function testManageResultWithScores($score)
    {
        $subject = new ScoreWriterService();
        $this->expectException(InvalidScoreException::class);
        $subject->store(['score' => $score]);
    }

    public function testStore()
    {
        $subject = new ScoreWriterService();
        $subject->setServiceLocator($this->getServiceLocator());
        $result = $subject->store(['score' => '0.92', 'deliveryExecutionId' => $this->deliveryExecutionId, 'sourcedId' => $this->sourcedId]);
        $this->assertEquals($this->deliveryExecutionId, $result);
    }

    public function testStoreWithDeliveryExecutionException()
    {
        $subject = new ScoreWriterService();
        $this->expectException(ResultException::class);
        $subject->setServiceLocator($this->getServiceLocator(true));
        $result = $subject->store(['score' => '0.92', 'deliveryExecutionId' => $this->deliveryExecutionId, 'sourcedId' => $this->sourcedId]);
        $this->assertEquals($this->deliveryExecutionId, $result);
    }

    private function getDeliveryExecutionMock($withThrow = false)
    {
        $deliveryExecutionMock = $this->getMockBuilder(DeliveryExecution::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIdentifier', 'getDelivery'])
            ->getMock();
        $deliveryExecutionMock->method('getIdentifier')->willReturn($this->deliveryExecutionId);

        if ($withThrow) {
            $deliveryExecutionMock->method('getDelivery')->willThrowException(new \Exception('Msg', 700));
        } else {
            $deliveryExecutionMock->method('getDelivery')->willReturn(true);
        }

        return $deliveryExecutionMock;
    }

    /**
     * @param bool $withDeliveryExecutionThrowException
     * @return MockObject|ServiceLocatorInterface
     */
    private function getServiceLocator($withDeliveryExecutionThrowException = false)
    {
        $deliveryExecutionMock = $this->getDeliveryExecutionMock($withDeliveryExecutionThrowException);

        $serviceProxyMock = $this->getMockBuilder(ServiceProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDeliveryExecution'])
            ->getMockForAbstractClass();
        $serviceProxyMock->method('getDeliveryExecution')
            ->willReturn($deliveryExecutionMock);

        $resultStorageServiceMock = $this->getMockBuilder(WritableResultStorage::class)
            ->disableOriginalConstructor()
            ->setMethods(['storeTestVariable'])
            ->getMockForAbstractClass();
        $resultStorageServiceMock->method('storeTestVariable')
            ->with($this->deliveryExecutionId, '', $this->anything(), '')
            ->willReturn(true);

        $resultServerServiceMock = $this->getMockBuilder(ResultServerService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResultStorage'])
            ->getMockForAbstractClass();
        $resultServerServiceMock->method('getResultStorage')
            ->with($this->sourcedId)->willReturn($resultStorageServiceMock);

        /** @var ServiceLocatorInterface|MockObject $serviceLocator */
        $serviceLocator = $this->getMockBuilder(ServiceManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        $serviceLocator->method('get')
            ->withConsecutive([ServiceProxy::SERVICE_ID], [ResultServerService::SERVICE_ID])
            ->willReturnOnConsecutiveCalls($serviceProxyMock, $resultServerServiceMock);

        return $serviceLocator;
    }
}
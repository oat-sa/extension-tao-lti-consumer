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
namespace oat\taoLtiConsumer\model\result;

use common_exception_InvalidArgumentType;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoLtiConsumer\model\result\validator\ValidateScoreResult;
use oat\taoResultServer\models\classes\ResultServerService;
use taoResultServer_models_classes_OutcomeVariable as ResultServerOutcomeVariable;
use Throwable;

class ScoreWriterService extends ConfigurableService
{
    /**
     * Store the score result into a delivery execution
     *
     * @param array $result
     *
     * @return string
     * @throws Throwable
     */
    public function store(array $result)
    {
        $this->validateResult($result);

        $deliveryExecution = $this->getDeliveryExecution($result);

        /** @var ResultServerService $resultServerService */
        $resultServerService = $this->getResultServerService();

        $resultStorageService = $resultServerService->getResultStorage($result['sourcedId']);

        $resultStorageService->storeTestVariable(
            $result['sourcedId'],
            $deliveryExecution->getDelivery()->getUri(),
            $this->getScoreVariable($result['score']),
            $deliveryExecution->getIdentifier()
        );

        return $deliveryExecution->getIdentifier();
    }

    /**
     * Look for a DeliveryExecution and return it or throw an Exception
     *
     * @param array $result
     *
     * @return DeliveryExecution
     * @throws ResultException
     */
    private function getDeliveryExecution($result)
    {
        try {
            /** @var ServiceProxy $resultService */
            $resultService = $this->getServiceLocator()->get(ServiceProxy::SERVICE_ID);
            return $resultService->getDeliveryExecution($result['sourcedId']);
        } catch (Throwable $exception) {
            throw new ResultException(
                $exception->getMessage(),
                MessageBuilder::STATUS_DELIVERY_EXECUTION_NOT_FOUND,
                $exception,
                MessageBuilder::build(MessageBuilder::STATUS_DELIVERY_EXECUTION_NOT_FOUND, $result)
            );
        }
    }

    /**
     * @return ResultServerService
     */
    private function getResultServerService()
    {
        return $this->getServiceLocator()->get(ResultServerService::SERVICE_ID);
    }

    /**
     * @param string $score
     *
     * @return ResultServerOutcomeVariable
     * @throws common_exception_InvalidArgumentType
     */
    private function getScoreVariable($score)
    {
        $scoreVariable = $this->getResultServiceOutcomeVariable();
        $scoreVariable->setIdentifier('SCORE');
        $scoreVariable->setCardinality(ResultServerOutcomeVariable::CARDINALITY_SINGLE);
        $scoreVariable->setBaseType('float');
        $scoreVariable->setEpoch(microtime());
        $scoreVariable->setValue($score);

        return $scoreVariable;
    }

    /**
     * @param array $result
     *
     * @throws InvalidScoreException
     * @throws ResultException
     */
    private function validateResult(array $result)
    {
        $validator = new ValidateScoreResult();
        $validator->validate($result);
    }

    /**
     * @return ResultServerOutcomeVariable
     */
    private function getResultServiceOutcomeVariable()
    {
        return new ResultServerOutcomeVariable();
    }
}

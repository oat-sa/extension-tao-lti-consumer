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

use common_exception_Error;
use common_exception_InvalidArgumentType;
use common_exception_NotFound;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoResultServer\models\classes\ResultServerService;
use oat\taoResultServer\models\Exceptions\DuplicateVariableException;
use taoResultServer_models_classes_OutcomeVariable as ResultServerOutcomeVariable;

class ScoreWriterService extends ConfigurableService
{
    const SCORE_VAR_NAME = 'SCORE';

    use LoggerAwareTrait;

    /**
     * Store the score result into a delivery execution
     *
     * @param DeliveryExecutionInterface $deliveryExecution
     * @param string $score should be validated before the call
     * @return bool true if saved, false if already exists
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    public function store(DeliveryExecutionInterface $deliveryExecution, $score)
    {
        $deliveryExecutionId = $deliveryExecution->getIdentifier();
        $resultServerService = $this->getResultServerService();
        $resultStorageService = $resultServerService->getResultStorage($deliveryExecutionId);

        try {
            $resultStorageService->storeTestVariable(
                $deliveryExecutionId,
                $deliveryExecution->getDelivery()->getUri(),
                $this->createScoreVariable($score),
                $deliveryExecutionId
            );
            return true;
        } catch (DuplicateVariableException $exception) {
            $this->logWarning(sprintf(
                'Attempt to add duplicated "%s" variable to the delivery execution "%s" results',
                self::SCORE_VAR_NAME,
                $deliveryExecutionId
            ));
            return false;
        }
    }

    /**
     * @return ResultServerService
     */
    private function getResultServerService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(ResultServerService::SERVICE_ID);
    }

    /**
     * @param string $score
     * @return ResultServerOutcomeVariable
     * @throws common_exception_InvalidArgumentType
     */
    private function createScoreVariable($score)
    {
        $scoreVariable = new ResultServerOutcomeVariable();
        $scoreVariable->setIdentifier(self::SCORE_VAR_NAME);
        $scoreVariable->setCardinality(ResultServerOutcomeVariable::CARDINALITY_SINGLE);
        $scoreVariable->setBaseType('float');
        $scoreVariable->setEpoch(microtime());
        $scoreVariable->setValue($score);

        return $scoreVariable;
    }
}

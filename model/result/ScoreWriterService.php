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
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoResultServer\models\classes\ResultServerService;
use oat\taoResultServer\models\Exceptions\DuplicateVariableException;
use taoResultServer_models_classes_OutcomeVariable as ResultServerOutcomeVariable;

/**
 * Class LtiXmlFormatterService
 * Class to manage XML result data with score and to store it in DeliveryExecution
 * @package oat\taoLtiConsumer\model\classes
 */
class ScoreWriterService extends ConfigurableService
{
    /**
     * Store the score result into a delivery execution
     * @param $result
     * @return string
     * @throws ResultException
     * @throws common_exception_Error
     * @throws DuplicateVariableException
     */
    public function store($result)
    {
        if (!(isset($result['score']) && $this->isScoreValid($result['score']))) {
            throw new InvalidScoreException(
                MessageBuilder::STATUSES[MessageBuilder::STATUS_INVALID_SCORE],
                MessageBuilder::STATUS_INVALID_SCORE,
                null,
                MessageBuilder::buildMessageData(MessageBuilder::STATUS_INVALID_SCORE, $result)
            );
        }

        if (!isset($result['sourcedId'])) {
            throw new ResultException(
                MessageBuilder::STATUSES[MessageBuilder::STATUS_DELIVERY_EXECUTION_NOT_FOUND],
                MessageBuilder::STATUS_DELIVERY_EXECUTION_NOT_FOUND,
                null,
                MessageBuilder::buildMessageData(MessageBuilder::STATUS_DELIVERY_EXECUTION_NOT_FOUND, $result)
            );
        }

        $deliveryExecution = $this->getDeliveryExecution($result);

        /** @var ResultServerService $resultServerService */
        $resultServerService = $this->getServiceLocator()->get(ResultServerService::SERVICE_ID);
        $resultStorageService = $resultServerService->getResultStorage($result['sourcedId']);
        $resultStorageService->storeTestVariable(
            $result['sourcedId'],
            $deliveryExecution->getDelivery()->getUri(),
            $this->getScoreVariable($result['score']
            ),
            $deliveryExecution->getIdentifier()
        );

        return $deliveryExecution->getIdentifier();
    }

    /**
     * Look for a DeliveryExecution and return it or throw an Exception
     * @param array $result
     * @throws ResultException
     * @return DeliveryExecution
     */
    private function getDeliveryExecution($result)
    {
        try {
            /** @var ServiceProxy $resultService */
            $resultService = $this->getServiceLocator()->get(ServiceProxy::SERVICE_ID);
            $deliveryExecution = $resultService->getDeliveryExecution($result['sourcedId']);
            $deliveryExecution->getDelivery();
        } catch (\Exception $e) {
            throw new ResultException($e->getMessage(), MessageBuilder::STATUS_DELIVERY_EXECUTION_NOT_FOUND, null,
                MessageBuilder::buildMessageData(MessageBuilder::STATUS_DELIVERY_EXECUTION_NOT_FOUND, $result)
            );
        }

        return $deliveryExecution;
    }

    /**
     * TODO: Move ResultServerOutcomeVariable creation into a factory
     * @param string $score
     * @return ResultServerOutcomeVariable
     * @throws common_exception_InvalidArgumentType
     */
    private function getScoreVariable($score)
    {
        $scoreVariable = new ResultServerOutcomeVariable();
        $scoreVariable->setIdentifier('SCORE');
        $scoreVariable->setCardinality(ResultServerOutcomeVariable::CARDINALITY_SINGLE);
        $scoreVariable->setBaseType('float');
        $scoreVariable->setEpoch(microtime());
        $scoreVariable->setValue($score);

        return $scoreVariable;
    }

    /**
     * @param mixed $score
     * @return bool
     */
    private function isScoreValid($score)
    {
        return (is_numeric($score) && $score >= 0 && $score <= 1);
    }
}

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
use oat\oatbox\event\EventManager;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManagerAwareTrait;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoLtiConsumer\model\result\parser\XmlResultParser;
use oat\taoResultServer\models\classes\ResultServerService;
use taoResultServer_models_classes_OutcomeVariable as OutcomeVariable;

/**
 * Class ResultService
 * Class to manage XML result data with score and to store it in DeliveryExecution
 * @package oat\taoLtiConsumer\model\classes
 */
class ResultService extends ConfigurableService
{
    const SERVICE_ID = 'taoLtiConsumer/resultService';
//    const LIS_SCORE_RECEIVE_EVENT = 'LisScoreReceivedEvent';
//    const DELIVERY_EXECUTION_ID = 'DeliveryExecutionID';


    public function processPayload($payload)
    {
        try {
            $parser = $this->getXmlResultParser($payload);
            $action = $parser->getRequestType();
            $data = $parser->getData();

            if (!method_exists($this, $action)) {
                throw new \InvalidArgumentException();
            }

            return call_user_func_array([$this, $action], $data);

        } catch (\Exception $e) {
            throw new ResultException('An error has occured', MessagesService::STATUS_METHOD_NOT_IMPLEMENTED, $e);
        }
    }

    protected function replaceResult(array $data)
    {
//        return 'ok';
        $score = $data;
        $messageIdentifier = '';

        if (!$this->isScoreValid($score)) {
            throw new ResultException(
//                self::$statuses[self::STATUS_INVALID_SCORE], self::STATUS_INVALID_SCORE, null, [
//                self::TEMPLATE_VAR_CODE_MAJOR => self::FAILURE_MESSAGE,
//                self::TEMPLATE_VAR_DESCRIPTION => self::$statuses[self::STATUS_INVALID_SCORE],
//                self::TEMPLATE_VAR_MESSAGE_ID => $messageIdentifier,]
            );
        }

//        $deliveryExecution = $this->getDeliveryExecution($result);

        /** @var EventManager $eventManager*/
//        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
//        $eventManager->trigger(self::LIS_SCORE_RECEIVE_EVENT,
//            [self::DELIVERY_EXECUTION_ID => $deliveryExecution->getIdentifier()]);


        /** @var ResultServerService $resultServerService */
//        $resultServerService = $this->getServiceLocator()->get(ResultServerService::SERVICE_ID);
//        $resultStorageService = $resultServerService->getResultStorage($result['sourcedId']);
//        $resultStorageService->storeTestVariable($result['sourcedId'], '', $this->resultService->getScoreVariable($result), '');

//        ['{{sourceId}}', '{{score}}'],
//                [$result['sourcedId'], $result['score']],
//                self::SCORE_DESCRIPTION_TEMPLATE),
//            self::TEMPLATE_VAR_MESSAGE_ID => $result['messageIdentifier'],
//            self::TEMPLATE_VAR_MESSAGE_REF_IDENTIFIER => $result['sourcedId'],

        return [
//            self::TEMPLATE_VAR_CODE_MAJOR => self::SUCCESS_MESSAGE,
//            self::TEMPLATE_VAR_DESCRIPTION => str_replace(
//                ['{{sourceId}}', '{{score}}'],
//                [$result['sourcedId'], $result['score']],
//                self::SCORE_DESCRIPTION_TEMPLATE),
//            self::TEMPLATE_VAR_MESSAGE_ID => $result['messageIdentifier'],
//            self::TEMPLATE_VAR_MESSAGE_REF_IDENTIFIER => $result['sourcedId'],
        ];
    }

    /**
     * @param string $payload The xml to parse
     * @return XmlResultParser
     */
    protected function getXmlResultParser($payload)
    {
        return $this->getServiceLocator()->get(XmlResultParser::class)->parse($payload);
    }

    /**
     * @param array $result
     * @return DeliveryExecution
     * @throws ResultException
     */
    public function getDeliveryExecution($result)
    {
        try {
            /** @var ServiceProxy $resultService */
            $resultService = $this->getServiceManager()->get(ServiceProxy::SERVICE_ID);
            $deliveryExecution = $resultService->getDeliveryExecution($result['sourcedId']);
        } catch (\Exception $e) {
            throw new ResultException(
//                $e->getMessage(), self::STATUS_DELIVERY_EXECUTION_NOT_FOUND, null, [
//                self::TEMPLATE_VAR_CODE_MAJOR => self::FAILURE_MESSAGE,
//                self::TEMPLATE_VAR_DESCRIPTION => self::$statuses[self::STATUS_DELIVERY_EXECUTION_NOT_FOUND],
//                self::TEMPLATE_VAR_MESSAGE_ID => $result['messageIdentifier'],]
            );
        }

        return $deliveryExecution;
    }

    /**
     * @param array $result
     * @return OutcomeVariable
     * @throws common_exception_InvalidArgumentType
     */
    public function getScoreVariable($result)
    {
        $scoreVariable = new OutcomeVariable();
        $scoreVariable->setIdentifier('SCORE');
        $scoreVariable->setCardinality(OutcomeVariable::CARDINALITY_SINGLE);
        $scoreVariable->setBaseType('float');
        $scoreVariable->setEpoch(microtime());
        $scoreVariable->setValue($result['score']);

        return $scoreVariable;
    }

    /**
     * @param mixed $score
     * @return bool
     */
    protected function isScoreValid($score)
    {
        return (is_numeric($score) && $score >= 0 && $score <= 1);
    }
}

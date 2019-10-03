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

use oat\oatbox\event\EventManager;
use oat\oatbox\service\ConfigurableService;
use oat\taoLtiConsumer\model\result\parser\XmlResultParser;

/**
 * Class ResultService
 *
 * Class to manage XML result data with score and to store it in DeliveryExecution
 *
 * @package oat\taoLtiConsumer\model\result
 */
class ResultService extends ConfigurableService
{
    const SERVICE_ID = 'taoLtiConsumer/resultService';

    const LIS_SCORE_RECEIVE_EVENT = 'LisScoreReceivedEvent';
    const DELIVERY_EXECUTION_ID = 'DeliveryExecutionID';

    /**
     * Process the incoming payload
     *
     * - Parse xml payload and extract data
     * - Forward to specific action to handle the payload request type
     *
     * @param $payload string
     * @return array
     * @throws ResultException
     */
    public function processPayload($payload)
    {
        try {
            $request = $this->getXmlResultParser($payload);
            $action = $request->getRequestType();

            if (!method_exists($this, $action)) {
                throw ResultException::fromCode(MessageBuilder::STATUS_METHOD_NOT_IMPLEMENTED);
            }

            return $this->$action($request->getData());

        } catch (\Exception $e) {
            $this->logError($e->getMessage());

            if (!$e instanceof ResultException) {
                $e = ResultException::fromCode(MessageBuilder::STATUS_INTERNAL_SERVER_ERROR, $e);
            }

            throw $e;
        }
    }

    protected function replaceResult(array $data)
    {
        $deliveryExecutionIdentifier = $this->getScoreWriter()->store($data);

        /** @var EventManager $eventManager*/
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        $eventManager->trigger(self::LIS_SCORE_RECEIVE_EVENT,
            [self::DELIVERY_EXECUTION_ID => $deliveryExecutionIdentifier]);

        return MessageBuilder::buildMessageData(MessageBuilder::STATUS_SUCCESS, $data);
    }

    /**
     * @param string $payload The xml to parse
     * @return XmlResultParser
     */
    protected function getXmlResultParser($payload)
    {
        return $this->getServiceLocator()->get(XmlResultParser::SERVICE_ID)->parse($payload);
    }

    /**
     * @return ScoreWriterService
     */
    protected function getScoreWriter()
    {
        return $this->getServiceLocator()->get(ScoreWriterService::class);
    }
}

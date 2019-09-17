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
 * Class to manage XML result data with score and to store it in DeliveryExecution
 * @package oat\taoLtiConsumer\model\classes
 */
class ResultService extends ConfigurableService
{
    const SERVICE_ID = 'taoLtiConsumer/resultService';

    const LIS_SCORE_RECEIVE_EVENT = 'LisScoreReceivedEvent';
    const DELIVERY_EXECUTION_ID = 'DeliveryExecutionID';

    /**
     * @param $payload
     * @return array
     * @throws ResultException
     */
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
            if (!$e instanceof ResultException) {
                $e = ResultException::fromCode(MessagesService::STATUS_INTERNAL_SERVER_ERROR, $e);
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

        return MessagesService::buildMessageData(MessagesService::STATUS_SUCCESS, $data);
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
     * @return ScoreWriterService
     */
    protected function getScoreWriter()
    {
        return $this->getServiceLocator()->get(ScoreWriterService::class);
    }
}

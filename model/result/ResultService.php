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
use oat\oatbox\event\EventManager;
use oat\oatbox\service\ConfigurableService;
use oat\taoLtiConsumer\model\result\event\ResultReadyEvent;
use oat\taoLtiConsumer\model\result\parser\XmlResultParser;
use oat\taoResultServer\models\Exceptions\DuplicateVariableException;
use Throwable;

class ResultService extends ConfigurableService
{
    public const SERVICE_ID = 'taoLtiConsumer/resultService';

    /**
     * @param string $payload
     *
     * @return mixed
     * @throws ResultException
     * @throws Throwable
     */
    public function process($payload)
    {
        try {
            $request = $this->getXmlResultParser($payload);
            $action = $request->getRequestType();

            if (!method_exists($this, $action)) {
                throw ResultException::fromCode(MessageBuilder::STATUS_METHOD_NOT_IMPLEMENTED);
            }

            return $this->$action($request->getData());
        } catch (Throwable $exception) {
            $this->logError($exception->getMessage());

            if (!$exception instanceof ResultException) {
                $exception = ResultException::fromCode(MessageBuilder::STATUS_INTERNAL_SERVER_ERROR, $exception);
            }

            throw $exception;
        }
    }

    /**
     * this dynamically called when the action from the call is replaceResult
     * @param array $data
     *
     * @return array
     * @throws ResultException
     * @throws common_exception_Error
     * @throws DuplicateVariableException
     */
    protected function replaceResult(array $data)
    {
        $deliveryExecutionIdentifier = $this->getScoreWriter()->store($data);

        /** @var EventManager $eventManager*/
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        $eventManager->trigger(new ResultReadyEvent($deliveryExecutionIdentifier));

        return MessageBuilder::build(MessageBuilder::STATUS_SUCCESS, $data);
    }

    /**
     * @param string $payload
     *
     * @return mixed
     */
    private function getXmlResultParser($payload)
    {
        return $this->getServiceLocator()->get(XmlResultParser::SERVICE_ID)->parse($payload);
    }

    /**
     * @return ScoreWriterService
     */
    private function getScoreWriter()
    {
        return $this->getServiceLocator()->get(ScoreWriterService::class);
    }
}

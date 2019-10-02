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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoLtiConsumer\controller;

use oat\taoLtiConsumer\model\result\MessagesService;
use oat\taoLtiConsumer\model\result\ResultService as LtiResultService;
use oat\taoLtiConsumer\model\result\ResultException;
use oat\taoLtiConsumer\model\result\XmlFormatterService;
use tao_actions_RestController as RestController;
use function GuzzleHttp\Psr7\stream_for;

/**
 * Class ResultController
 * @package oat\taoLtiConsumer\controller
 */
class ResultController extends RestController
{
    /**
     * Endpoint to manage result
     */
    public function manageResults()
    {
        $this->logDebug('Entered into manageResult +=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=');

        try {
            $payload = $this->getPsrRequest()->getBody()->getContents();
            $data = $this->getLtiResultService()->processPayload($payload);
            $code = MessagesService::STATUS_SUCCESS;
        } catch (ResultException $e) {
            $data = $e->getOptionalData();
            $code = $e->getCode();
        }

        $this->response = $this->getPsrResponse()
            ->withStatus($code)
            ->withBody(
                stream_for($this->getResponseFormatter()->getXmlResponse($data))
            );
    }

    /**
     * @return LtiResultService
     */
    protected function getLtiResultService()
    {
        return $this->getServiceLocator()->get(LtiResultService::class);
    }

    /**
     * @return XmlFormatterService
     */
    protected function getResponseFormatter()
    {
        return $this->getServiceLocator()->get(XmlFormatterService::class);
    }
}

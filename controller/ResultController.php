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

use common_user_auth_AuthFailedException;
use common_user_User;
use oat\taoLti\models\classes\Lis\LisAuthAdapter;
use oat\taoLtiConsumer\model\result\MessageBuilder;
use oat\taoLtiConsumer\model\result\ResultException;
use oat\taoLtiConsumer\model\result\ResultService as LtiResultService;
use oat\taoLtiConsumer\model\result\XmlFormatterService;
use Psr\Http\Message\ServerRequestInterface;
use tao_actions_CommonModule;
use function GuzzleHttp\Psr7\stream_for;

/**
 * Class ResultController
 * @package oat\taoLtiConsumer\controller
 */
class ResultController extends tao_actions_CommonModule
{
    public function manageResults()
    {
        try {
            $this->authenticate($this->getPsrRequest());

            $payload = $this->getPsrRequest()->getBody()->getContents();
            $data = $this->getLtiResultService()->processPayload($payload);
            $code = MessageBuilder::STATUS_SUCCESS;
        } catch (ResultException $exception) {
            //todo:: add proper 403
            $data = $exception->getOptionalData();
            $code = $exception->getCode();
        }

        $this->response = $this->getPsrResponse()
            ->withStatus($code)
            ->withBody(
                stream_for($this->getResponseFormatter()->getXmlResponse($data))
            );
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return common_user_User
     * @throws common_user_auth_AuthFailedException
     */
    private function authenticate(ServerRequestInterface $request)
    {
        /** @var LisAuthAdapter $adaptor */
        $adaptor = $this->propagate(new LisAuthAdapter($request));

        return $adaptor->authenticate();
    }

    /**
     * @return LtiResultService
     */
    private function getLtiResultService()
    {
        return $this->getServiceLocator()->get(LtiResultService::class);
    }

    /**
     * @return XmlFormatterService
     */
    private function getResponseFormatter()
    {
        return $this->getServiceLocator()->get(XmlFormatterService::class);
    }
}

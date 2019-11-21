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

namespace oat\taoLtiConsumer\model\result\operations;

use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseInterface;

class BasicResponse implements LisOutcomeResponseInterface
{
    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $statusDescription;

    /**
     * @var string
     */
    private $codeMajor;

    /**
     * @var string
     */
    private $messageIdentifier;

    /**
     * @var string|null
     */
    private $messageRefIdentifier;

    /**
     * @var string|null
     */
    private $operationRefIdentifier;

    /**
     * @param string $status
     * @param string $statusDescription
     * @param string $codeMajor
     * @param string $messageIdentifier
     * @param string $messageRefIdentifier|null
     * @param string $operationRefIdentifier|null
     */
    public function __construct(
        $status,
        $statusDescription,
        $codeMajor,
        $messageIdentifier = null,
        $messageRefIdentifier = null,
        $operationRefIdentifier = null
    ) {
        $this->status = $status;
        $this->statusDescription = $statusDescription;
        $this->codeMajor = $codeMajor;
        $this->messageIdentifier = $messageIdentifier ?? $this->generateMessageIdentifier($messageRefIdentifier);
        $this->messageRefIdentifier = $messageRefIdentifier;
        $this->operationRefIdentifier = $operationRefIdentifier;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getStatusDescription()
    {
        return $this->statusDescription;
    }

    /**
     * @return string
     */
    public function getCodeMajor()
    {
        return $this->codeMajor;
    }

    /**
     * @return string
     */
    public function getMessageIdentifier()
    {
        return $this->messageIdentifier;
    }

    /**
     * @return string|null
     */
    public function getMessageRefIdentifier()
    {
        return $this->messageRefIdentifier;
    }

    /**
     * @return string|null
     */
    public function getOperationRefIdentifier()
    {
        return $this->operationRefIdentifier;
    }

    /**
     * $requestContextPrefix is any string which depends on request to decrease probability of collisions
     * @param string|null $requestContextPrefix
     * @return string
     */
    protected function generateMessageIdentifier($requestContextPrefix = null)
    {
        return md5(
            microtime() .
            mt_rand() .
            gethostname() .
            ($requestContextPrefix ?? '')
        );
    }
}

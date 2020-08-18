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

declare(strict_types=1);

namespace oat\taoLtiConsumer\model\result\operations;

use oat\taoLtiConsumer\model\result\messages\LisOutcomeResponseInterface;

class BasicResponse implements LisOutcomeResponseInterface
{
    /** @var string */
    private $status;

    /** @var string */
    private $statusDescription;

    /** @var string */
    private $codeMajor;

    /** @var string */
    private $messageIdentifier;

    /** @var string|null */
    private $messageRefIdentifier;

    /** @var string|null */
    private $operationRefIdentifier;

    public function __construct(
        string $status,
        string $statusDescription,
        string $codeMajor,
        string $messageIdentifier = null,
        string $messageRefIdentifier = null,
        string $operationRefIdentifier = null
    ) {
        $this->status = $status;
        $this->statusDescription = $statusDescription;
        $this->codeMajor = $codeMajor;
        $this->messageIdentifier = $messageIdentifier ?? $this->generateMessageIdentifier($messageRefIdentifier);
        $this->messageRefIdentifier = $messageRefIdentifier;
        $this->operationRefIdentifier = $operationRefIdentifier;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStatusDescription(): string
    {
        return $this->statusDescription;
    }

    public function getCodeMajor(): string
    {
        return $this->codeMajor;
    }

    public function getMessageIdentifier(): string
    {
        return $this->messageIdentifier;
    }

    public function getMessageRefIdentifier(): string
    {
        return $this->messageRefIdentifier;
    }

    public function getOperationRefIdentifier(): string
    {
        return $this->operationRefIdentifier;
    }

    /**
     * $requestContextPrefix is any string which depends on request to decrease probability of collisions
     */
    protected function generateMessageIdentifier(string $requestContextPrefix = null): string
    {
        return md5(
            microtime() .
            mt_rand() .
            gethostname() .
            ($requestContextPrefix ?? '')
        );
    }
}

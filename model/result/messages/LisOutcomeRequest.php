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

namespace oat\taoLtiConsumer\model\result\messages;

use oat\taoLtiConsumer\model\result\operations\OperationRequestInterface;

/**
 * Represents parsed message for LTI outcomes service
 * Contains only fields needed for current service implementation
 * @see https://www.imsglobal.org/specs/ltiomv1p0/specification
 */
class LisOutcomeRequest
{
    /** @var string */
    private $messageIdentifier;

    /** @var string  */
    private $operationName;

    /** @var OperationRequestInterface|null  */
    private $operation;

    public function __construct(
        string $messageIdentifier,
        string $operationName,
        OperationRequestInterface $operation = null
    ) {
        $this->messageIdentifier = $messageIdentifier;
        $this->operationName = $operationName;
        $this->operation = $operation;
    }

    public function getMessageIdentifier(): string
    {
        return $this->messageIdentifier;
    }

    public function getOperationName(): string
    {
        return $this->operationName;
    }

    public function getOperation(): ?OperationRequestInterface
    {
        return $this->operation;
    }
}

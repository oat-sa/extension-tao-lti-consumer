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
 * Copyright (c) 2019-2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoLtiConsumer\model\result\messages;

interface LisOutcomeResponseInterface
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_UNSUPPORTED = 'unsupported';
    public const STATUS_INVALID_REQUEST = 'invalid_request';
    public const STATUS_NOT_FOUND = 'not_found';
    public const STATUS_INTERNAL_ERROR = 'internal_error';

    public const CODE_MAJOR_SUCCESS = 'success';
    public const CODE_MAJOR_FAILURE = 'failure';
    public const CODE_MAJOR_UNSUPPORTED = 'unsupported';
    public const CODE_MAJOR_PROCESSING = 'processing';

    public function getStatus(): string;

    public function getStatusDescription(): string;

    public function getCodeMajor(): string;

    public function getMessageIdentifier(): string;

    public function getMessageRefIdentifier(): string;

    public function getOperationRefIdentifier(): string;
}

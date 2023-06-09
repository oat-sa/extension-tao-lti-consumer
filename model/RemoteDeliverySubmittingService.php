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
 * Copyright (c) 2023 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoLtiConsumer\model;

use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\tao\helpers\UrlHelper;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\DeliveryExecutionService;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

class RemoteDeliverySubmittingService
{
    private const EXECUTION_ID_QUERY_PARAM = 'execution';
    private const LTI_ERROR_MSG_QUERY_PARAM = 'lti_errormsg';
    private const LTI_ERROR_LOG_QUERY_PARAM = 'lti_errorlog';
    private const IRRECOVERABLE_ERROR_LOG_HINT = '[IRRECOVERABLE]';

    private UrlHelper $urlHelper;
    private DeliveryExecutionService $deliveryExecutionService;

    public function __construct(UrlHelper $urlHelper, DeliveryExecutionService $deliveryExecutionService)
    {
        $this->urlHelper = $urlHelper;
        $this->deliveryExecutionService = $deliveryExecutionService;
    }

    public function provideSubmitUrl(string $executionId): string
    {
        return $this->urlHelper->buildUrl(
            'submitRemoteExecution',
            'ResultController',
            'taoLtiConsumer',
            [self::EXECUTION_ID_QUERY_PARAM => $executionId]
        );
    }

    public function submitRemoteExecution(array $queryParams): void
    {
        if (!array_key_exists(self::EXECUTION_ID_QUERY_PARAM, $queryParams)) {
            throw new RuntimeException('Execution id is not provided');
        }
        $deliveryExecution = $this->deliveryExecutionService->getDeliveryExecution(
            $queryParams[self::EXECUTION_ID_QUERY_PARAM]
        );

        if (
            in_array(
                $deliveryExecution->getState()->getUri(),
                [DeliveryExecutionInterface::STATE_TERMINATED, DeliveryExecutionInterface::STATE_FINISHED]
            )
        ) {
            return;
        }

        if (
            !(
                array_key_exists(self::LTI_ERROR_MSG_QUERY_PARAM, $queryParams)
                || array_key_exists(self::LTI_ERROR_LOG_QUERY_PARAM, $queryParams)
            )
        ) {
            $deliveryExecution->setState(DeliveryExecutionInterface::STATE_FINISHED);
            return;
        }

        if (
            array_key_exists(self::LTI_ERROR_LOG_QUERY_PARAM, $queryParams)
            && strpos($queryParams[self::LTI_ERROR_LOG_QUERY_PARAM], self::IRRECOVERABLE_ERROR_LOG_HINT) !== false
        ) {
            $deliveryExecution->setState(DeliveryExecutionInterface::STATE_TERMINATED);
            return;
        }

        throw new RuntimeException($queryParams[self::LTI_ERROR_MSG_QUERY_PARAM] ?? 'Unknown error');
    }
}

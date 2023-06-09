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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA ;
 */

declare(strict_types=1);

namespace oat\taoLtiConsumer\model\ServiceProvider;

use oat\generis\model\data\Ontology;
use oat\generis\model\DependencyInjection\ContainerServiceProviderInterface;

use oat\tao\helpers\UrlHelper;
use oat\taoDelivery\model\execution\DeliveryExecutionService;
use oat\taoLti\models\classes\Lis\LisAuthAdapterFactory;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;
use oat\taoLti\models\classes\Security\AccessTokenRequestValidator;
use oat\taoLtiConsumer\model\delivery\lookup\DeliveryLookupByDeliveryExecution;
use oat\taoLtiConsumer\model\ltiProvider\repository\DeliveryLtiProviderRepository;
use oat\taoLtiConsumer\model\RemoteDeliverySubmittingService;
use oat\taoLtiConsumer\model\result\messages\LisOutcomeRequestParser;
use oat\taoLtiConsumer\model\result\operations\replace\Service\Lti1p1ReplaceResultParser;
use oat\taoLtiConsumer\model\result\operations\replace\Service\Lti1p3ReplaceResultParser;
use oat\taoLtiConsumer\model\result\operations\replace\Service\LtiReplaceResultParserProxy;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

class ContainerServiceProvider implements ContainerServiceProviderInterface
{
    public function __invoke(ContainerConfigurator $configurator): void
    {
        $services = $configurator->services();

        $services
            ->set(Lti1p1ReplaceResultParser::class, Lti1p1ReplaceResultParser::class)
            ->args(
                [
                    service(LisOutcomeRequestParser::class),
                    service(LisAuthAdapterFactory::class),
                ]
            );

        $services
            ->set(Lti1p3ReplaceResultParser::class, Lti1p3ReplaceResultParser::class)
            ->args(
                [
                    service(LisOutcomeRequestParser::class),
                    service(DeliveryLtiProviderRepository::class),
                    service(AccessTokenRequestValidator::class),
                ]
            );

        $services
            ->set(LtiReplaceResultParserProxy::class, LtiReplaceResultParserProxy::class)
            ->public()
            ->args(
                [
                    service(Lti1p1ReplaceResultParser::class),
                    service(Lti1p3ReplaceResultParser::class),
                ]
            );

        $services
            ->set(DeliveryLookupByDeliveryExecution::class, DeliveryLookupByDeliveryExecution::class)
            ->public()
            ->args(
                [
                    service(DeliveryExecutionService::SERVICE_ID)
                ]
            );

        $services
            ->set(DeliveryLtiProviderRepository::class, DeliveryLtiProviderRepository::class)
            ->public()
            ->args(
                [
                    service(LtiProviderService::SERVICE_ID),
                    service(Ontology::SERVICE_ID),
                    [
                        service(DeliveryLookupByDeliveryExecution::class)
                    ]
                ]
            );

        $services
            ->set(RemoteDeliverySubmittingService::class, RemoteDeliverySubmittingService::class)
            ->public()
            ->args(
                [
                    service(UrlHelper::class),
                    service(DeliveryExecutionService::SERVICE_ID)
                ]
            );
    }
}

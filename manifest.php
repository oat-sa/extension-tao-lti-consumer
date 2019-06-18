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

use oat\taoLtiConsumer\scripts\install\RegisterLtiDeliveryContainer;
use oat\taoLtiConsumer\scripts\update\Updater;

return [
    'name' => 'taoLtiConsumer',
    'label' => 'TAO LTI Consumer',
    'description' => 'TAO LTI Consumer extension',
    'license' => 'GPL-2.0',
    'version' => '0.1.2',
    'author' => 'Open Assessment Technologies SA',
    'requires' => [
        'taoLti' => '>=9.2.1',
        'taoDeliveryRdf' => '>=8.0.1',
    ],
    'acl' => [
        ['grant', 'http://www.tao.lu/Ontologies/generis.rdf#taoLtiConsumerManager', ['ext'=>'taoLtiConsumer']],
    ],
    'install' => [
        'rdf' => [],
        'php'	=> [
            RegisterLtiDeliveryContainer::class
        ],
    ],
    'update' => Updater::class,
    'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#taoLtiConsumerManager',
    'routes' => [
        '/taoLtiConsumer' => 'oat\\taoLtiConsumer\\controller'
    ],
    'constants' => [
        'DIR_VIEWS' => __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR,
    ],
    'extra' => [
        'structures' => __DIR__ . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'structures.xml',
    ]
];

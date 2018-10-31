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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 */

use oat\tao\model\user\TaoRoles;
use oat\taoLtiConsumer\scripts\install\RegisterTaoConsumer;
use oat\taoLtiConsumer\scripts\install\RegisterLtiConsumerDeliveryRendererHelperService;

$extpath = dirname(__FILE__).DIRECTORY_SEPARATOR;

return array(
    'name' => 'taoLtiConsumer',
    'label' => 'TAO LTI Consumer',
    'description' => 'TAO LTI Consumer extension',
    'license' => 'GPL-2.0',
    'version' => '0.0.1',
    'author' => 'Open Assessment Technologies SA',
    'requires' => array(
        'ltiDeliveryProvider' => '>=6.5.4',
        'taoDelivery' => '>=10.1.0'
    ),
    'update' => 'oat\\taoLtiConsumer\\scripts\\update\\Updater',
    'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#taoDeliveryRdfManager',
    'install' => array(
        'php' => array(
            RegisterTaoConsumer::class,
            RegisterLtiConsumerDeliveryRendererHelperService::class,
        )
    ),
    'acl' => array(
        array('grant', TaoRoles::ANONYMOUS, array('ext'=>'taoLtiConsumer', 'mod' => 'LtiConsumer', 'act' => 'launchToolProvider')),
        array('grant', TaoRoles::ANONYMOUS, array('ext'=>'taoLtiConsumer', 'mod' => 'LtiConsumer', 'act' => 'stopToolProvider')),
    ),
    'uninstall' => array(
    ),
    'routes' => array(
        '/taoLtiConsumer' => 'oat\\taoLtiConsumer\\controller'
    ),
    'constants' => array(
        'DIR_VIEWS' => $extpath . 'views' . DIRECTORY_SEPARATOR
    ),
    'extra' => array(
        'structures' => __DIR__.DIRECTORY_SEPARATOR.'controller'.DIRECTORY_SEPARATOR.'structures.xml',
    ),
);

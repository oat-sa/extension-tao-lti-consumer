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

namespace oat\taoLtiConsumer\model\delivery\container;

use oat\tao\helpers\Template;
use oat\taoDelivery\model\container\execution\AbstractExecutionContainer;

/**
 * Class DeliveryClientContainer
 */
class LtiExecutionContainer extends AbstractExecutionContainer
{
    const LOADER_TEMPLATE = 'container/loader.tpl';
    const CONTENT_TEMPLATE = 'container/ltiExecutionContainerForm.tpl';

    /**
     * Name of the extension containing the loader template.
     */
    const TEMPLATE_EXTENSION = 'taoLtiConsumer';

    protected function getHeaderTemplate()
    {
        return Template::getTemplate(self::LOADER_TEMPLATE, self::TEMPLATE_EXTENSION);
    }

    protected function getBodyTemplate()
    {
        return Template::getTemplate(self::CONTENT_TEMPLATE, self::TEMPLATE_EXTENSION);
    }
}

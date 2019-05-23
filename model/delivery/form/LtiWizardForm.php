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
 *
 */

namespace oat\taoLtiConsumer\model\delivery\form;

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\taoDeliveryRdf\view\form\WizardForm;
use oat\taoLti\models\classes\ProviderService;

class LtiWizardForm extends WizardForm
{
    protected function initForm()
    {
        $this->form = new \tao_helpers_form_xhtml_Form('simpleLtiWizard');

        $createElt = \tao_helpers_form_FormFactory::getElement('create', 'Free');
        $createElt->setValue('<button class="form-submitter btn-success small" type="button"><span class="icon-publish"></span> ' .__('Publish').'</button>');
        $this->form->setDecorators([
            'actions-bottom' => new \tao_helpers_form_xhtml_TagWrapper(['tag' => 'div', 'cssClass' => 'form-toolbar']),
        ]);
        $this->form->setActions(array(), 'top');
        $this->form->setActions(array($createElt), 'bottom');

    }

    /**
     * @return mixed|void
     * @throws \common_Exception
     */
    public function initElements()
    {
        $class = $this->data['class'];
        if (!$class instanceof \core_kernel_classes_Class) {
            throw new \common_Exception('Missing class in lti delivery creation form');
        }

        $classUriElt = \tao_helpers_form_FormFactory::getElement('classUri', 'Hidden');
        $classUriElt->setValue($class->getUri());
        $this->form->addElement($classUriElt);

        /** @var ComplexSearchService $search */
        $search = $this->getServiceManager()->get(ComplexSearchService::SERVICE_ID);
        $queryBuilder = $search->query();
        $query = $search->searchType($queryBuilder , ProviderService::CLASS_URI, true);
        $queryBuilder->setCriteria($query);

        $count = $search->getGateway()->count($queryBuilder);

        if (0 === $count) {
            throw new NoLtiProviderException();
        }

        $selectProviderElt = \tao_helpers_form_FormFactory::getElement('ltiProviderSelect', 'Free');
        $selectProviderElt->setValue('<div class="lti-provider-select-container"></div>');
        $this->form->addElement($selectProviderElt);

        $ltiProviderElt = \tao_helpers_form_FormFactory::getElement('ltiProvider', 'Hidden');
        $ltiProviderElt->addValidator(\tao_helpers_form_FormFactory::getValidator('NotEmpty'));
        $this->form->addElement($ltiProviderElt);

        $ltiPathElt = \tao_helpers_form_FormFactory::getElement('ltiPathElt', 'TextArea');
        $ltiPathElt->addValidator(\tao_helpers_form_FormFactory::getValidator('NotEmpty'));
        $ltiPathElt->setDescription(__('Provide the LTI URL of A LTI compatible test'));
        $this->form->addElement($ltiPathElt);
    }

}
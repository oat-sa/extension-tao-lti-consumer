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

use common_Exception;
use core_kernel_classes_Class as RdfClass;
use oat\taoDeliveryRdf\view\form\WizardForm;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;
use tao_helpers_form_FormFactory as FormFactory;
use tao_helpers_form_xhtml_Form as XhtmlForm;
use tao_helpers_form_xhtml_TagWrapper as TagWrapper;

class LtiWizardForm extends WizardForm
{
    /**
     * @return void
     * @throws common_Exception
     */
    protected function initForm()
    {
        $this->form = new XhtmlForm('simpleLtiWizard');

        $createElt = FormFactory::getElement('create', 'Free');
        $createElt->setValue('<button class="form-submitter btn-success small" type="button"><span class="icon-publish"></span> ' . __('Publish') . '</button>');
        $this->form->setDecorators([
            'actions-bottom' => new TagWrapper(['tag' => 'div', 'cssClass' => 'form-toolbar']),
        ]);
        $this->form->setActions([], 'top');
        $this->form->setActions([$createElt], 'bottom');
    }

    /**
     * @return void
     * @throws NoLtiProviderException If there is no LTI provider
     * @throws common_Exception
     * @throws \common_exception_Error
     */
    public function initElements()
    {
        $class = $this->data['class'];
        if (!$class instanceof RdfClass) {
            throw new common_Exception('Missing class in lti delivery creation form');
        }

        $classUriElt = FormFactory::getElement('classUri', 'Hidden');
        $classUriElt->setValue($class->getUri());
        $this->form->addElement($classUriElt);

        /** @var LtiProviderService $ltiProviderService */
        $ltiProviderService = $this->getServiceManager()->get(LtiProviderService::SERVICE_ID);
        if ($ltiProviderService->count() === 0) {
            throw new NoLtiProviderException();
        }

        $selectProviderElt = FormFactory::getElement('ltiProviderSelect', 'Free');
        $selectProviderElt->setValue('<div class="lti-provider-select-container"></div>');
        $this->form->addElement($selectProviderElt);

        $ltiProviderElt = FormFactory::getElement('ltiProvider', 'Hidden');
        $ltiProviderElt->addValidator(FormFactory::getValidator('NotEmpty'));
        $this->form->addElement($ltiProviderElt);

        $ltiPathElt = FormFactory::getElement('ltiPathElt', 'TextArea');
        $ltiPathElt->addValidator(FormFactory::getValidator('NotEmpty'));
        $ltiPathElt->setDescription(__('Provide the LTI URL of A LTI compatible test'));
        $this->form->addElement($ltiPathElt);
    }
}

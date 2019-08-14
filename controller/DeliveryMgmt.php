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

namespace oat\taoLtiConsumer\controller;

use oat\generis\model\OntologyAwareTrait;
use oat\tao\model\taskQueue\Task\TaskInterface;
use oat\taoLtiConsumer\model\delivery\factory\LtiDeliveryFactory;
use oat\taoLtiConsumer\model\delivery\form\LtiWizardForm;
use oat\tao\model\taskQueue\TaskLogActionTrait;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoDeliveryRdf\model\tasks\CompileDelivery;
use oat\taoDeliveryRdf\view\form\WizardForm;
use oat\taoDeliveryRdf\model\NoTestsException;
use oat\taoLti\models\classes\LtiProvider\LtiProviderService;
use oat\taoLtiConsumer\model\delivery\form\NoLtiProviderException;

/**
 * Controller to managed assembled deliveries
 *
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @package taoDelivery
 */
class DeliveryMgmt extends \tao_actions_RdfController
{
    use OntologyAwareTrait;
    use TaskLogActionTrait;

    /**
     * Manage the delivery wizard form
     *
     * Render a creation form if form has not been submitted
     * If has been validated use WizardFormFactory to enqueue the delivery creation
     *
     * @return mixed
     * @throws \common_Exception
     * @throws \common_ext_ExtensionException
     */
    public function wizard()
    {
        $this->defaultData();
        $formOptions = [
            'class' => $this->getCurrentClass(),
        ];

        $noAvailableTest = $noAvailableProvider = false;

        try {

            try {
                $compiledDeliveryForm = (new WizardForm($formOptions))->getForm();
                if ($compiledDeliveryForm->isSubmited()) {

                        return $this->returnTaskJson($this->validateCompiledDeliveryForm($compiledDeliveryForm));

                } else {
                    $this->setData('compiled-delivery-form', $compiledDeliveryForm->render());
                }
            } catch (NoTestsException $e) {
                $noAvailableTest = true;
                $this->setData('compiled-form-message', __('There are currently no tests available to publish.'));
            }

            try {
                $ltiDeliveryForm = (new LtiWizardForm($formOptions))->getForm();
                if ($ltiDeliveryForm->isSubmited()) {
                    return $this->returnTaskJson($this->validateLtiDeliveryForm($ltiDeliveryForm));
                } else {
                    $this->setData('lti-delivery-form', $ltiDeliveryForm->render());
                }
            } catch (NoLtiProviderException $e) {
                $noAvailableProvider = true;
                $this->setData('lti-form-message', __('There are currently no lti provider available.'));
            }

        } catch (\tao_helpers_form_Exception $e) {
            return $this->returnJson([
                'success' => false,
                'errorMsg' => $e->getMessage(),
                'errorCode' => 400,
            ]);
        }

        $this->setData('formTitle', __('Create a new delivery'));
        if ($noAvailableProvider === true && $noAvailableTest === true) {
            $this->setData('message', __('Delivery creation impossible without QTI test neither LTI provider.'));
            $this->setView('form/wizard_error.tpl', 'taoLtiConsumer');
        } else {
            $this->setView('form/LtiDeliveryWizardForm.tpl', 'taoLtiConsumer');
        }
    }

    /**
     * Prepare formatted for select2 component filtered list of available for LTI providers
     */
    public function getAvailableLtiProviders()
    {
        $this->returnJson($this->getLtiProvidersFromLtiProviderService());
    }

    /**
     * Performs the LtiProviders search.
     *
     * @return array
     */
    public function getLtiProvidersFromLtiProviderService()
    {
        $q = trim($this->getGetParameter('q'));

        $ltiProviderService = $this->getServiceLocator()->get(LtiProviderService::SERVICE_ID);

        $providers = $q === ''
            ? $ltiProviderService->findAll()
            : $ltiProviderService->searchByLabel($q);

        return ['total' => count($providers), 'items' => $providers];
    }

    /**
     * Validate Compiled delivery creation form
     * If the form is valid create a task for deferred compilation
     *
     * @param \tao_helpers_form_Form $compiledDeliveryForm
     * @return TaskInterface
     * @throws \tao_helpers_form_Exception
     */
    protected function validateCompiledDeliveryForm(\tao_helpers_form_Form $compiledDeliveryForm)
    {
        if (!$compiledDeliveryForm->isValid()) {
            throw new \tao_helpers_form_Exception(__('Compiled based delivery must be linked to a valid QTI test.'));
        }
        $test = $this->getResource($compiledDeliveryForm->getValue('test'));
        $deliveryClass = $this->getClass($compiledDeliveryForm->getValue('classUri'));
        /** @var DeliveryFactory $deliveryFactory */
        $deliveryFactory = $this->getServiceLocator()->get(DeliveryFactory::SERVICE_ID);
        $initialProperties = $deliveryFactory->getInitialPropertiesFromArray($compiledDeliveryForm->getValues());
        return CompileDelivery::createTask($test, $deliveryClass, $initialProperties);
    }

    /**
     * Validate LTI delivery creation form
     * If the form is valid create a task for deferred LTI delivery creation
     *
     * @param \tao_helpers_form_Form $ltiDeliveryForm
     * @return TaskInterface
     * @throws \tao_helpers_form_Exception
     */
    protected function validateLtiDeliveryForm(\tao_helpers_form_Form $ltiDeliveryForm)
    {
        if (!$ltiDeliveryForm->isValid()) {
            throw new \tao_helpers_form_Exception(__('LTI based delivery cannot be created without LTI provider and LTI test url.'));
        }

        $ltiProvider = $this->getResource($ltiDeliveryForm->getValue('ltiProvider'));
        $ltiPath = $ltiDeliveryForm->getValue('ltiPathElt');
        $deliveryClass = $this->getClass($ltiDeliveryForm->getValue('classUri'));

        /** @var LtiDeliveryFactory $deliveryFactory */
        $deliveryFactory = $this->getServiceLocator()->get(LtiDeliveryFactory::class);
        return $deliveryFactory->deferredCreate($deliveryClass, $ltiProvider, $ltiPath);
    }

    protected function getRootClass()
    {
        // Should not be called
    }
}

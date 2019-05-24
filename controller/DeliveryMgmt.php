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

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdfs;
use oat\taoLtiConsumer\model\delivery\factory\LtiDeliveryFactory;
use oat\taoLtiConsumer\model\delivery\form\LtiWizardForm;
use oat\tao\model\taskQueue\TaskLogActionTrait;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoDeliveryRdf\model\tasks\CompileDelivery;
use oat\taoDeliveryRdf\view\form\WizardForm;
use oat\taoDeliveryRdf\model\NoTestsException;
use oat\taoLti\models\classes\ProviderService;
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

                if ($compiledDeliveryForm->isSubmited() && $compiledDeliveryForm->isValid()) {
                    $test = $this->getResource($compiledDeliveryForm->getValue('test'));
                    $deliveryClass = $this->getClass($compiledDeliveryForm->getValue('classUri'));
                    /** @var DeliveryFactory $deliveryFactory */
                    $deliveryFactory = $this->getServiceLocator()->get(DeliveryFactory::SERVICE_ID);
                    $initialProperties = $deliveryFactory->getInitialPropertiesFromArray($compiledDeliveryForm->getValues());
                    return $this->returnTaskJson(CompileDelivery::createTask($test, $deliveryClass, $initialProperties));
                }

                $this->setData('compiled-delivery-form', $compiledDeliveryForm->render());
            } catch (NoTestsException $e) {
                $noAvailableTest = true;
            }

            try {
                $ltiDeliveryForm = (new LtiWizardForm($formOptions))->getForm();

                if ($ltiDeliveryForm->isSubmited() && $ltiDeliveryForm->isValid()) {
                    $ltiProvider = $this->getResource($ltiDeliveryForm->getValue('ltiProvider'));
                    $ltiPath = $ltiDeliveryForm->getValue('ltiPathElt');
                    $deliveryClass = $this->getClass($ltiDeliveryForm->getValue('classUri'));

                    /** @var LtiDeliveryFactory $deliveryFactory */
                    $deliveryFactory = $this->getServiceLocator()->get(LtiDeliveryFactory::class);
                    return $this->returnTaskJson(
                        $deliveryFactory->deferredCreate($deliveryClass, $ltiProvider, $ltiPath)
                    );
                }

                $this->setData('lti-delivery-form', $ltiDeliveryForm->render());
            } catch (NoLtiProviderException $e) {
                $noAvailableProvider = true;
            }

            if ($noAvailableProvider === true && $noAvailableTest === true) {
                throw new \Exception('Delivery creation impossible without qti test neither lti provider.');
            }

        } catch (\Exception $e) {
            return $this->returnJson([
                'success' => false,
                'errorMsg' => $e instanceof \common_exception_UserReadableException ? $e->getUserMessage() : $e->getMessage(),
                'errorCode' => $e->getCode(),
            ]);
        }

        $this->setData('formTitle', __('Create a new delivery'));
        $this->setView('form/LtiDeliveryWizardForm.tpl', 'taoLtiConsumer');
    }

    /**
     * Prepare formatted for select2 component filtered list of available for LTI providers
     */
    public function getAvailableLtiProviders()
    {
        $q = trim($this->getGetParameter('q'));
        $providers = [];

        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);
        try {
            $queryBuilder = $search->query();
            $query = $search->searchType($queryBuilder, ProviderService::CLASS_URI, true)
                ->add(OntologyRdfs::RDFS_LABEL)
                ->contains($q);
            $queryBuilder->setCriteria($query);
            $result = $search->getGateway()->search($queryBuilder);
        } catch (\Exception $e) {
            $this->logError('Unable to retrieve providers: ' . $e->getMessage());
            $result = [];
        }

        foreach ($result as $provider) {
            try {
                $providerUri = $provider->getUri();
                $providers[] = ['id' => $providerUri, 'uri' => $providerUri, 'text' => $provider->getLabel()];
            } catch (\Exception $e) {
                $this->logWarning('Unable to load items for test ' . $providerUri);
            }
        }
        $this->returnJson(['total' => count($providers), 'items' => $providers]);
    }

    protected function getRootClass()
    {
        // Should not be called
    }
}

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
use oat\generis\model\OntologyRdfs;
use oat\taoLtiConsumer\model\delivery\factory\LtiDeliveryFactory;
use oat\taoLtiConsumer\model\delivery\form\LtiWizardForm;
use oat\oatbox\event\EventManager;
use oat\tao\model\taskQueue\TaskLogActionTrait;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoDeliveryRdf\model\tasks\CompileDelivery;
use oat\taoDeliveryRdf\view\form\WizardForm;
use oat\taoDeliveryRdf\model\NoTestsException;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoLti\models\classes\ProviderService;

/**
 * Controller to managed assembled deliveries
 *
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @package taoDelivery
 */
class DeliveryMgmt extends \tao_actions_SaSModule
{
    use TaskLogActionTrait;

    /**
     * @return EventManager
     */
    protected function getEventManager()
    {
        return $this->getServiceLocator()->get(EventManager::SERVICE_ID);
    }

    /**
     * (non-PHPdoc)
     * @see \tao_actions_SaSModule::getClassService()
     */
    protected function getClassService()
    {
        if (!$this->service) {
            $this->service = DeliveryAssemblyService::singleton();
        }
        return $this->service;
    }

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
            'isToggable' => true
        ];

        $noAvailableTest = $noAvailableProvider = false;

        try {
            try {
                $compiledDeliveryForm = (new WizardForm($formOptions))->getForm();

                if ($compiledDeliveryForm->isSubmited() && $compiledDeliveryForm->isValid()) {
                    $test = $this->getResource($compiledDeliveryForm->getValue('test'));
                    $deliveryClass = $this->getClass($compiledDeliveryForm->getValue('classUri'));
                    /** @var DeliveryFactory $deliveryFactoryResources */
                    $deliveryFactoryResources = $this->getServiceLocator()->get(DeliveryFactory::SERVICE_ID);
                    $initialProperties = $deliveryFactoryResources->getInitialPropertiesFromArray($compiledDeliveryForm->getValues());
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

                    /** @var LtiDeliveryFactory $factory */
                    $factory = $this->propagate(new LtiDeliveryFactory());
                    $report = $factory->create($deliveryClass, $this->getResource($ltiProvider), $ltiPath);

                    return $this->returnReport($report);
                }
            } catch (NoTestsException $e) {
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

        $this->setData('lti-delivery-form', $ltiDeliveryForm->render());
        $this->setData('formTitle', __('Create a new delivery'));
        $this->setView('form/LtiDeliveryWizardForm.tpl', 'taoLtiConsumer');
    }

    /**
     * Prepare formatted for select2 component filtered list of available for compilation tests
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function getAvailableLtiProviders()
    {
        $q = $this->getRequestParameter('q');
        $tests = [];

//        $testService = \taoTests_models_classes_TestsService::singleton();
        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);

        $queryBuilder = $search->query();
        $query = $search->searchType($queryBuilder , ProviderService::CLASS_URI, true)
            ->add(OntologyRdfs::RDFS_LABEL)
            ->contains($q);

        $queryBuilder->setCriteria($query);

        $result = $search->getGateway()->search($queryBuilder);

        $result = (new \core_kernel_classes_Class(ProviderService::CLASS_URI))->getInstances(true);
        foreach ($result as $test) {
//        var_dump($test);
            try {
//                $testItems = $testService->getTestItems($test);
                //Filter tests which has no items
//                if (!empty($testItems)) {
                    $testUri = $test->getUri();
                    $tests[] = ['id' => $testUri, 'uri' => $testUri, 'text' => $test->getLabel()];
//                }
            } catch (\Exception $e) {
                $this->logWarning('Unable to load items for test ' . $testUri);
            }
        }
        $this->returnJson(['total' => count($tests), 'items' => $tests]);
    }

    /**
     * @param array $options
     * @throws \common_exception_IsAjaxAction
     */
    protected function getTreeOptionsFromRequest($options = [])
    {
        $config = $this->getServiceLocator()->get('taoDeliveryRdf/DeliveryMgmt')->getConfig();
        $options['order'] = key($config['OntologyTreeOrder']);
        $options['orderdir'] = $config['OntologyTreeOrder'][$options['order']];
        return parent::getTreeOptionsFromRequest($options);
    }
}

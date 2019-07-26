/*
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
 * Copyright (c) 2019 (original work) Open Assessment Technlogies SA
 *
 */

define([
    'jquery',
    'i18n',
    'taoDeliveryRdf/util/providers',
    'taoLtiConsumer/util/providers',
    'taoDeliveryRdf/util/forms/inputBehaviours',
    'ui/tabs',
    'css!taoLtiConsumerCss/wizard.css'
], function($, __, testProviders, ltiProviders, inputBehaviours, tabsComponent) {
    'use strict';

    const providers = Object.assign({}, testProviders, ltiProviders);

    return {
        // Builds form elements & defines button actions
        start() {
            const $multiForm = $('.multi-form-container');

            const $compiledForm = $('#simpleWizard');
            const $ltiForm = $('#simpleLtiWizard');

            const $testFilterContainer = $compiledForm.find('.test-select-container');
            const $providerFilterContainer = $ltiForm.find('.lti-provider-select-container');

            const $testFormElement = $('input#test');
            const $providerFormElement = $('input#ltiProvider');

            const $compiledContainer = $compiledForm.closest('.content-block');
            const $ltiContainer = $ltiForm.closest('.content-block');

            tabsComponent({
                renderTo: $('.tab-selector', $multiForm),
                tabs: [
                    { label: __('TAO Local'), name: 'tao-local' },
                    { label: __('LTI-based'), name: 'lti-based' }
                ]
            });

            // Replace submit button with taskQueue requester
            const taskButton = inputBehaviours.replaceSubmitWithTaskButton({
                $form: $compiledForm,
                $reportContainer: $compiledContainer,
                buttonTitle: __('Publish the test'),
                buttonLabel: __('Publish')
            });

            // Replace submit button with taskQueue requester
            const ltiTaskButton = inputBehaviours.replaceSubmitWithTaskButton({
                $form: $ltiForm,
                $reportContainer: $ltiContainer,
                buttonTitle: __('Publish the test'),
                buttonLabel: __('Publish')
            });

            // Enhanced selector input for tests:
            inputBehaviours.createSelectorInput({
                $filterContainer: $testFilterContainer,
                $formElement: $testFormElement,
                taskButton: taskButton,
                dataProvider: {
                    list: providers.listTests
                },
                inputPlaceholder: __('Select the test you want to publish to the test-takers'),
                inputLabel: __('Select the test')
            });

            // Enhanced selector input for LTI providers:
            inputBehaviours.createSelectorInput({
                $filterContainer: $providerFilterContainer,
                $formElement: $providerFormElement,
                taskButton: ltiTaskButton,
                dataProvider: {
                    list: providers.listLtiProviders
                },
                inputPlaceholder: __('Select the Provider you want to publish'),
                inputLabel: __('LTI Provider')
            });
        }
    };
});

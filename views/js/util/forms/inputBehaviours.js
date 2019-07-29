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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
/**
 * @author Martin Nicholson <martin@taotesting.com>
 */
define([
    'jquery',
    'i18n',
    'taoDeliveryRdf/util/forms/inputBehaviours'
], function ($, __, inputBehaviours) {
    'use strict';

    return {
        /**
         * Set up the wizard form for publishing a LTI-based delivery
         * @param {jQuery} $form
         * @param {Object} providers - contains function(s) for fetching data
         */
        setupLtiForm($form, providers) {
            const $reportContainer = $form.closest('.content-block');
            const $providerFilterContainer = $('.lti-provider-select-container', $form);
            const $providerInputElement = $('#ltiProvider', $form);

            // Replace submit button with taskQueue requester
            const ltiTaskButton = inputBehaviours.replaceSubmitWithTaskButton({
                $form,
                $reportContainer
            });

            // Enhanced selector input for LTI providers:
            inputBehaviours.createSelectorInput({
                $filterContainer: $providerFilterContainer,
                $inputElement: $providerInputElement,
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

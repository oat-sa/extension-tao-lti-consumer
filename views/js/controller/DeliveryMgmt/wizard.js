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
/**
 * @author Martin Nicholson <martin@taotesting.com>
 */
define([
    'jquery',
    'taoDeliveryRdf/util/providers/testsProvider',
    'taoLtiConsumer/util/providers/ltiProvider',
    'taoDeliveryRdf/util/forms/deliveryFormHelper',
    'taoLtiConsumer/util/forms/deliveryFormHelper',
    'ui/tabs',
    'css!taoLtiConsumerCss/wizard.css'
], function($, testsProvider, ltiProvider, rdfDeliveryFormHelper, ltiDeliveryFormHelper, tabsFactory) {
    'use strict';

    // Extend data & behaviour providers:
    const providers = Object.assign({}, testsProvider, ltiProvider);
    const deliveryFormHelper = Object.assign({}, rdfDeliveryFormHelper, ltiDeliveryFormHelper);

    return {
        /**
         * Creates a tabs component if multiple forms rendered
         * Initialises the special input fields in the forms
         * @returns {void}
         */
        start() {
            const $multiForm = $('.multi-form-container');

            // Extract tabs config from HTML data attrs:
            const tabsData = $('[data-tab-content]', $multiForm)
                .toArray()
                .map(el => ({
                    label: $(el).data('tab-label'),
                    name: $(el).data('tab-content')
                }));

            const $tabContainer = $('.tab-selector', $multiForm);
            const $tabsConstentContainer = $('.main-container');

            tabsFactory($tabContainer, {
                showHideTarget: $tabsConstentContainer,
                tabs: tabsData
            });

            const tabNames = tabsData.map(t => t.name);

            if (tabNames.includes('tao-local')) {
                this.setupTaoLocalForm();
            }
            if (tabNames.includes('lti-based')) {
                this.setupLtiForm();
            }
        },

        // calls setup from taoDeliveryRdf
        setupTaoLocalForm() {
            const $tabContent = $('[data-tab-content="tao-local"]');
            const $form = $('#simpleWizard', $tabContent);

            deliveryFormHelper.setupTaoLocalForm($form, providers);
        },

        // calls setup from taoLtiConsumer
        setupLtiForm() {
            const $tabContent = $('[data-tab-content="lti-based"]');
            const $form = $('#simpleLtiWizard', $tabContent);

            deliveryFormHelper.setupLtiForm($form, providers);
        }
    };
});

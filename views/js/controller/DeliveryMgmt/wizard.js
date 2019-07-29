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
    'i18n',
    'taoDeliveryRdf/util/providers',
    'taoLtiConsumer/util/providers',
    'taoDeliveryRdf/util/forms/inputBehaviours',
    'taoLtiConsumer/util/forms/inputBehaviours',
    'ui/tabs',
    'css!taoLtiConsumerCss/wizard.css'
], function($, __, testProviders, ltiProviders, testInputBehaviours, ltiInputBehaviours, tabsComponent) {
    'use strict';

    // Extend data & behaviour providers:
    const providers = Object.assign({}, testProviders, ltiProviders);
    const inputBehaviours = Object.assign({}, testInputBehaviours, ltiInputBehaviours);

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

            if (tabsData.length > 1) {
                tabsComponent({
                    renderTo: $('.tab-selector', $multiForm),
                    tabs: tabsData
                });
            }

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

            inputBehaviours.setupTaoLocalForm($form, providers);
        },

        // calls setup from taoLtiConsumer
        setupLtiForm() {
            const $tabContent = $('[data-tab-content="lti-based"]');
            const $form = $('#simpleLtiWizard', $tabContent);

            inputBehaviours.setupLtiForm($form, providers);
        }
    };
});

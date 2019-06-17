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
define([
    'lodash',
    'jquery',
    'i18n',
    'ui/filter',
    'ui/feedback',
    'util/url',
    'layout/actions',
    'core/promise',
    'ui/taskQueue/taskQueue',
    'ui/taskQueueButton/standardButton',
    'ui/switch/switch',
    'css!/taoLtiConsumer/views/css/wizard.css'
], function(_, $, __, filterFactory, feedback, urlUtils, actionManager, Promise, taskQueue, taskCreationButtonFactory, switchFactory) {
    'use strict';

    var provider = {
        /**
         * List available Lti providers
         * @param {Object} data - the query parameters
         * @returns {Promise}
         */
        listProviders: function listProviders(data) {
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: urlUtils.route('getAvailableLtiProviders', 'DeliveryMgmt', 'taoLtiConsumer'),
                    data: {
                        q: data.q,
                        page: data.page
                    },
                    type: 'GET',
                    dataType: 'JSON'
                }).done(function(tests) {
                    if (tests) {
                        resolve(tests);
                    } else {
                        reject(new Error(__('Unable to load lti providers')));
                    }
                }).fail(function() {
                    reject(new Error(__('Unable to load lti providers')));
                });
            });
        },

        /**
         * List available tests
         * @param {Object} data - the query parameters
         * @returns {Promise}
         */
        listTests: function listTests(data) {
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: urlUtils.route('getAvailableTests', 'DeliveryMgmt', 'taoDeliveryRdf'),
                    data: {
                        q: data.q,
                        page: data.page
                    },
                    type: 'GET',
                    dataType: 'JSON'
                }).done(function(tests) {
                    if (tests) {
                        resolve(tests);
                    } else {
                        reject(new Error(__('Unable to load tests')));
                    }
                }).fail(function() {
                    reject(new Error(__('Unable to load tests')));
                });
            });
        }
    };

    /**
     * wrapped the old jstree API used to refresh the tree and optionally select a resource
     * @param {String} [uriResource] - the uri resource node to be selected
     */
    var refreshTree = function refreshTree(uriResource){
        actionManager.trigger('refresh', {
            uri : uriResource
        });
    };

    return {
        // Builds form elements & defines button actions
        // TODO: refactor into some logical functions and deduplicate a little
        start: function() {
            var $multiForm = $('.multi-form-container');
            var $switch = $('.form-switch', $multiForm);

            var $compiledForm = $('#simpleWizard');
            var $ltiForm = $('#simpleLtiWizard');

            var $testFilterContainer = $compiledForm.find('.test-select-container');
            var $providerFilterContainer = $ltiForm.find('.lti-provider-select-container');

            var $testFormElement = $('#test');
            var $providerFormElement = $('#ltiProvider');

            var $compiledFormContentBlock = $('.compiled-delivery-form-content');
            var $ltiFormContentBlock = $('.lti-delivery-form-content');

            var $compiledContainer = $compiledForm.closest('.content-block');
            var $ltiContainer = $ltiForm.closest('.content-block');

            var $oldCompiledSubmitter = $compiledForm.find('.form-submitter');
            var $oldLtiSubmitter = $ltiForm.find('.form-submitter');

            var taskCompiledCreationButton;
            var taskLtiCreationButton;

            $ltiFormContentBlock.addClass('hidden');

            switchFactory($switch, {
                off: {
                    label: __('TAO delivery'),
                    active: true
                },
                on: {
                    label: __('LTI based delivery'),
                },
                monoStyle: true
            })
            .on('change', function() {
                $compiledFormContentBlock.toggleClass('hidden');
                $ltiFormContentBlock.toggleClass('hidden');
            });

            filterFactory($testFilterContainer, {
                placeholder: __('Select the test you want to publish to the test-takers'),
                width: '64%',
                quietMillis: 1000,
                label: __('Select the test')
            })
            .on('change', function(chosenTest) {
                $testFormElement.val(chosenTest);
                if (chosenTest) {
                    taskCompiledCreationButton.enable();
                } else {
                    taskCompiledCreationButton.disable();
                }
            })
            .on('request', function(params) {
                provider
                    .listTests(params.data)
                    .then(function(tests) {
                        params.success(tests);
                    })
                    .catch(function(err) {
                        params.error(err);
                        feedback().error(err);
                    });
            })
            .render('<%- text %>');

            filterFactory($providerFilterContainer, {
                placeholder: __('Select the Provider you want to publish'),
                width: '64%',
                quietMillis: 1000,
                label: __('LTI Provider')
            })
            .on('change', function(chosenProvider) {
                $providerFormElement.val(chosenProvider);
                if (chosenProvider) {
                    taskLtiCreationButton.enable();
                } else {
                    taskLtiCreationButton.disable();
                }
            })
            .on('request', function(params) {
                provider
                    .listProviders(params.data)
                    .then(function(tests) {
                        params.success(tests);
                    })
                    .catch(function(err) {
                        params.error(err);
                        feedback().error(err);
                    });
            })
            .render('<%- text %>');

            //find the old submitter and replace it with the new component
            taskCompiledCreationButton = taskCreationButtonFactory({
                type : 'info',
                icon : 'delivery',
                title : __('Publish the test'),
                label : __('Publish'),
                taskQueue : taskQueue,
                taskCreationUrl : $compiledForm.prop('action'),
                taskCreationData : function getTaskCreationData() {
                    return $compiledForm.serializeArray();
                },
                taskReportContainer : $compiledContainer
            })
            .on('finished', function(result){
                if (result.task
                    && result.task.report
                    && _.isArray(result.task.report.children)
                    && result.task.report.children.length
                    && result.task.report.children[0]) {
                    if (result.task.report.children[0].data
                        && result.task.report.children[0].data.uriResource) {
                        feedback().info(__('%s completed', result.task.taskLabel));
                        refreshTree(result.task.report.children[0].data.uriResource);
                    } else {
                        this.displayReport(result.task.report.children[0], __('Error'));
                    }
                }
            })
            .on('continue', function() {
                refreshTree();
            })
            .on('error', function(err) {
                //format and display error message to user
                feedback().error(err);
                this.trigger('finished');
            })
            .render($oldCompiledSubmitter.closest('.form-toolbar'))
            .disable();

            //replace the old submitter with the new one
            $oldCompiledSubmitter.replaceWith(taskCompiledCreationButton.getElement());

            taskLtiCreationButton = taskCreationButtonFactory({
                type : 'info',
                icon : 'delivery',
                title : __('Publish the test'),
                label : __('Publish'),
                taskQueue : taskQueue,
                taskCreationUrl : $ltiForm.prop('action'),
                taskCreationData : function getTaskCreationData(){
                    return $ltiForm.serializeArray();
                },
                taskReportContainer : $ltiContainer
            })
            .on('finished', function(result){
                if (result.task
                    && result.task.report
                    && _.isArray(result.task.report.children)
                    && result.task.report.children.length
                    && result.task.report.children[0]) {

                    if (result.task.report.children[0].data
                        && result.task.report.children[0].data.uriResource) {
                        feedback().info(__('%s completed', result.task.taskLabel));
                        refreshTree(result.task.report.children[0].data.uriResource);
                    } else {
                        this.displayReport(result.task.report.children[0], __('Error'));
                    }
                }
            })
            .on('continue', function(){
                refreshTree();
            })
            .on('error', function(err){
                //format and display error message to user
                feedback().error(err);
                this.trigger('finished');
            })
            .render($oldLtiSubmitter.closest('.form-toolbar'))
            .disable();

            //replace the old submitter with the new one
            $oldLtiSubmitter.replaceWith(taskLtiCreationButton.getElement());
        }
    };
});

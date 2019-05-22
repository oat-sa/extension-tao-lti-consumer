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
    'core/taskQueue/taskQueue',
    'ui/taskQueueButton/standardButton'
], function (_, $, __, filterFactory, feedback, urlUtils, actionManager, Promise, taskQueue, taskCreationButtonFactory) {
    'use strict';

    var provider = {

        /**
         * List available Lti providers
         * @returns {Promise}
         */
        listProviders: function listProviders(data) {
            return new Promise(function (resolve, reject) {
                $.ajax({
                    url: urlUtils.route('getAvailableLtiProviders', 'DeliveryMgmt', 'taoLtiConsumer'),
                    data: {
                        q: data.q,
                        page: data.page
                    },
                    type: 'GET',
                    dataType: 'JSON'
                }).done(function (tests) {
                    if (tests) {
                        resolve(tests);
                    } else {
                        reject(new Error(__('Unable to load lti providers')));
                    }
                }).fail(function () {
                    reject(new Error(__('Unable to load lti providers')));
                });
            });
        },

        listTests: function listTests(data) {
            return new Promise(function (resolve, reject) {
                $.ajax({
                    url: urlUtils.route('getAvailableTests', 'DeliveryMgmt', 'taoDeliveryRdf'),
                    data: {
                        q: data.q,
                        page: data.page
                    },
                    type: 'GET',
                    dataType: 'JSON'
                }).done(function (tests) {
                    if (tests) {
                        resolve(tests);
                    } else {
                        reject(new Error(__('Unable to load tests')));
                    }
                }).fail(function () {
                    reject(new Error(__('Unable to load tests')));
                });
            });
        },


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
        start: function () {
            var $testFilterContainer = $('.test-select-container');
            var $providerFilterContainer = $('.lti-provider-select-container');
            var $testFormElement = $('#test');
            var $providerFormElement = $('#ltiProvider');
            var $compiledForm = $('#simpleWizard');
            var $ltiForm = $('#simpleLtiWizard');
            var $compiledContainer = $compiledForm.closest('.content-block');
            var $ltiContainer = $ltiForm.closest('.content-block');
            var taskCompiledCreationButton, taskLtiCreationButton, $oldCompiledSubmitter, $oldLtiSubmitter;

            // $('.toggle-button').on('click', function (event) {
            //     event.preventDefault();
            //     $filterContainer.toggle();
            // });

            filterFactory($testFilterContainer, {
                placeholder: __('Select the test you want to publish to the test-takers'),
                width: '64%',
                quietMillis: 1000,
                label: __('Select the test')
            }).on('change', function (test) {
                $testFormElement.val(test);
                if(test){
                    taskCompiledCreationButton.enable();
                }else{
                    taskCompiledCreationButton.disable();
                }
            }).on('request', function (params) {
                provider
                    .listTests(params.data)
                    .then(function (tests) {
                        params.success(tests);
                    })
                    .catch(function (err) {
                        params.error(err);
                        feedback().error(err);
                    });
            }).render('<%- text %>');

            filterFactory($providerFilterContainer, {
                placeholder: __('Select the Provider you want to publish'),
                width: '64%',
                quietMillis: 1000,
                label: __('LTI Provider')
            }).on('change', function (provider) {
                $providerFormElement.val(provider);
                if(provider){
                    taskLtiCreationButton.enable();
                }else{
                    taskLtiCreationButton.disable();
                }
            }).on('request', function (params) {
                provider
                    .listProviders(params.data)
                    .then(function (tests) {
                        params.success(tests);
                    })
                    .catch(function (err) {
                        params.error(err);
                        feedback().error(err);
                    });
            }).render('<%- text %>');

            //find the old submitter and replace it with the new component
            $oldCompiledSubmitter = $compiledForm.find('.form-submitter');
            taskCompiledCreationButton = taskCreationButtonFactory({
                type : 'info',
                icon : 'delivery',
                title : __('Publish the test'),
                label : __('Publish'),
                taskQueue : taskQueue,
                taskCreationUrl : $compiledForm.prop('action'),
                taskCreationData : function getTaskCreationData(){
                    return $compiledForm.serializeArray();
                },
                taskReportContainer : $compiledContainer
            }).on('finished', function(result){
                if (result.task
                    && result.task.report
                    && _.isArray(result.task.report.children)
                    && result.task.report.children.length
                    && result.task.report.children[0]) {
                    if(result.task.report.children[0].data
                        && result.task.report.children[0].data.uriResource){
                        feedback().info(__('%s completed', result.task.taskLabel));
                        refreshTree(result.task.report.children[0].data.uriResource);
                    }else{
                        this.displayReport(result.task.report.children[0], __('Error'));
                    }
                }
            }).on('continue', function(){
                refreshTree();
            }).on('error', function(err){
                //format and display error message to user
                feedback().error(err);
            }).render($oldCompiledSubmitter.closest('.form-toolbar')).disable();
            $oldCompiledSubmitter.replaceWith(taskCompiledCreationButton.getElement().css({float: 'right'}));

            $oldLtiSubmitter = $ltiForm.find('.form-submitter');
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
            }).on('finished', function(result){
                if (result.task
                    && result.task.report
                    && _.isArray(result.task.report.children)
                    && result.task.report.children.length
                    && result.task.report.children[0]) {
                    if(result.task.report.children[0].data
                        && result.task.report.children[0].data.uriResource){
                        feedback().info(__('%s completed', result.task.taskLabel));
                        refreshTree(result.task.report.children[0].data.uriResource);
                    }else{
                        this.displayReport(result.task.report.children[0], __('Error'));
                    }
                }
            }).on('continue', function(){
                refreshTree();
            }).on('error', function(err){
                //format and display error message to user
                feedback().error(err);
            }).render($oldLtiSubmitter.closest('.form-toolbar')).disable();

            //replace the old submitter with the new one and apply its style
            $oldLtiSubmitter.replaceWith(taskLtiCreationButton.getElement().css({float: 'right'}));
        }
    };
});

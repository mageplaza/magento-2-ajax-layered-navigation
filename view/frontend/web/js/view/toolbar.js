/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_AjaxLayer
 * @copyright   Copyright (c) Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

define([
    'jquery',
    'Mageplaza_AjaxLayer/js/action/submit-filter',
    'productListToolbarForm'
], function ($, submitFilterAction) {
    'use strict';

    /**
     * AJAX toolbar (sort / limit / view-mode / pagination) for the product list.
     *
     * Lives on the BASE catalog/search handle (inside #layer-product-list), independent of the
     * layered-navigation widget, so sorting and pagination keep working on pages that have no
     * filters. Re-initialised on each AJAX swap because #layer-product-list is replaced.
     *
     * @param {Object} config
     * @param {HTMLElement} element - the #layer-product-list container
     */
    return function (config, element) {
        var isProcessToolbar = false;

        // Override the toolbar form to submit via AJAX instead of a full page reload.
        $.mage.productListToolbarForm.prototype.changeUrl = function (paramName, paramValue, defaultValue) {
            if (isProcessToolbar) {
                return;
            }
            isProcessToolbar = true;

            var urlPaths = this.options.url.split('?'),
                baseUrl = urlPaths[0],
                urlParams = urlPaths[1] ? urlPaths[1].split('&') : [],
                paramData = {},
                parameters;
            for (var i = 0; i < urlParams.length; i++) {
                parameters = urlParams[i].split('=');
                paramData[parameters[0]] = parameters[1] !== undefined
                    ? window.decodeURIComponent(parameters[1].replace(/\+/g, '%20'))
                    : '';
            }
            paramData[paramName] = paramValue;
            if (paramValue === defaultValue) {
                delete paramData[paramName];
            }
            paramData = $.param(paramData);
            submitFilterAction(baseUrl + (paramData.length ? '?' + paramData : ''));
        };

        // Bind AJAX to pagination links inside the swapped product list.
        $(element).find('.pages a').each(function () {
            var el = $(this),
                link = el.prop('href');
            if (!link) {
                return;
            }
            el.on('click', function (e) {
                submitFilterAction(link);
                e.stopPropagation();
                e.preventDefault();
            });
        });
    };
});

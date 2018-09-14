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

define(
    [
        'jquery',
        'mage/storage',
        'Mageplaza_AjaxLayer/js/model/loader'
    ],
    function ($, storage, loader) {
        'use strict';

        var productContainer = $('#layer-product-list'),
            layerContainer = $('.layered-filter-block-container');

        return function (submitUrl) {
            /** save active state */
            var actives = [];
            $('.filter-options-item').each(function (index) {
                if ($(this).hasClass('active')) {
                    actives.push($(this).attr('attribute'));
                }
            });
            window.layerActiveTabs = actives;

            /** start loader */
            loader.startLoader();

            /** change browser url */
            if (typeof window.history.pushState === 'function') {
                window.history.pushState({url: submitUrl}, '', submitUrl);
            }

            return storage.post(submitUrl, {}).done(
                function (response) {
                    if (response.backUrl) {
                        window.location = response.backUrl;
                        return;
                    }
                    if (response.ajax == 0) {
                        window.location.reload();
                    }
                    if (response.navigation) {
                        layerContainer.html(response.navigation);
                        layerContainer.trigger('contentUpdated');
                    }
                    if (response.products) {
                        productContainer.html(response.products);
                        productContainer.trigger('contentUpdated');
                    }
                }
            ).fail(
                function () {
                    window.location.reload();
                }
            ).always(
                function () {
                    loader.stopLoader();
                }
            );
        };
    }
);

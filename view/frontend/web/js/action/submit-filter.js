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
        'Mageplaza_AjaxLayer/js/model/loader',
        'mage/apply/main'
    ],
    function ($, storage, loader, mage) {
        'use strict';

        var productContainer = $('#layer-product-list'),
            layerContainer = $('.layered-filter-block-container'),
            quickViewContainer = $('#mpquickview-popup');

        return function (submitUrl, isChangeUrl) {
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
            if (typeof window.history.pushState === 'function' && (typeof isChangeUrl === 'undefined')) {
                window.history.pushState({url: submitUrl}, '', submitUrl);
            }

            return storage.post(submitUrl, {}).done(
                function (response) {
                    if (response.backUrl) {
                        window.location = response.backUrl;
                        return;
                    }
                    if (response.navigation) {
                        layerContainer.html(response.navigation);
                    }
                    if (response.products) {
                        productContainer.html(response.products);
                    }
                    if (response.quickview) {
                        quickViewContainer.html(response.quickview);
                    }

                    if (mage) {
                        mage.apply();
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

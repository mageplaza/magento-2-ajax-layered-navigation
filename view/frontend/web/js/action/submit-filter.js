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
        'mage/url',
        'Mageplaza_AjaxLayer/js/model/loader',
        'mage/apply/main',
        'ko'
    ],
    function ($, storage, urlBuilder, loader, mage, ko) {
        'use strict';

        var productContainer = $('#layer-product-list'),
            layerContainer   = $('.layered-filter-block-container');

        return function (submitUrl, isChangeUrl, method) {
            /** save active state */
            var actives = [],
                data;
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
            if (method === 'post') {// For 'add to wishlist' & 'add to compare' event
                return storage.post(submitUrl).done(
                ).fail(
                    function () {
                        window.location.reload();
                    }
                ).always(function () {
                    loader.stopLoader();
                });
            }

            /** Re-apply swatch color backgrounds and selected state after a DOM swap. */
            var applyColorSwatches = function () {
                var colorAttr = $('.filter-options .filter-options-item .color .swatch-option-link-layered .swatch-option');

                colorAttr.each(function(){
                    var el  = $(this),
                        hex = el.attr('data-option-tooltip-value');
                    if(typeof hex != "undefined"){
                        if (hex.charAt(0) === '#') {
                            hex = hex.substr(1);
                        }
                        if ((hex.length < 2) || (hex.length > 6)) {
                            el.attr('style','background: '+el.attr('data-option-label')+';');
                        }
                        var values = hex.split(''),
                            r,
                            g,
                            b;

                        if (hex.length === 2) {
                            r = parseInt(values[0].toString() + values[1].toString(), 16);
                            g = r;
                            b = r;
                        } else if (hex.length === 3) {
                            r = parseInt(values[0].toString() + values[0].toString(), 16);
                            g = parseInt(values[1].toString() + values[1].toString(), 16);
                            b = parseInt(values[2].toString() + values[2].toString(), 16);
                        } else if (hex.length === 6) {
                            r = parseInt(values[0].toString() + values[1].toString(), 16);
                            g = parseInt(values[2].toString() + values[3].toString(), 16);
                            b = parseInt(values[4].toString() + values[5].toString(), 16);
                        } else {
                            el.attr('style','background: '+el.attr('data-option-label')+';');
                        }

                        el.attr('style','background: center center no-repeat rgb('+[r, g, b]+');');
                    }
                });

                //selected
                var filterCurrent = $('.layered-filter-block-container .filter-current .items .item .filter-value');

                filterCurrent.each(function(){
                    var el         = $(this),
                        colorLabel = el.html(),
                        colorAttr  = $('.filter-options .filter-options-item .color .swatch-option-link-layered .swatch-option');

                    colorAttr.each(function(){
                        var elA = $(this);
                        if(elA.attr('data-option-label') === colorLabel && !elA.hasClass('selected')){
                            elA.addClass('selected');
                        }
                    });
                });
            };

            /**
             * Cache-agnostic navigation: fetch the FULL (cacheable) page with a plain GET — no
             * X-Requested-With, no JSON — so the request is byte-identical to a normal navigation
             * and shares the page's single cache entry on every layer (FPC, Varnish, Fastly, CDN).
             * Only #layer-product-list and .layered-filter-block-container are swapped in.
             */
            return fetch(urlBuilder.build(submitUrl), {
                method: 'GET',
                credentials: 'include'
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.text();
            }).then(function (html) {
                var $page = $('<div></div>').append($.parseHTML(html));

                var navigation = $page.find('.layered-filter-block-container').html();
                if (navigation != null) {
                    layerContainer.html(navigation);
                }

                var products = $page.find('#layer-product-list').html();
                if (products != null) {
                    productContainer.html(products);
                }

                // keep the robots meta tag in sync with the fetched page
                $page.find('meta[name="robots"]').each(function () {
                    $('head meta[name="robots"]').first().replaceWith($(this));
                });

                if (productContainer.length) {
                    ko.cleanNode(productContainer[0]);
                    productContainer.applyBindings();
                }
                if (mage) {
                    mage.apply();
                }

                applyColorSwatches();
                loader.stopLoader();
            }).catch(function () {
                window.location.reload();
            });
        };
    }
);

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
    'Magento_Catalog/js/price-utils',
    'jquery-ui-modules/widget',
    'accordion',
    'productListToolbarForm'
], function ($, submitFilterAction, ultil) {
    "use strict";

    $.widget('mageplaza.layer', $.mage.accordion, {
        options: {
            openedState: 'active',
            collapsible: true,
            multipleCollapsible: true,
            animate: 200,
            mobileShopbyElement: '#layered-filter-block .filter-title [data-role=title]',
            collapsibleElement: '[data-role=ln_collapsible]',
            header: '[data-role=ln_title]',
            content: '[data-role=ln_content]',
            isCustomerLoggedIn: false,
            params: [],
            active: []
        },

        _create: function () {
            this.initActiveItems();

            this._super();

            this.initProductListUrl();
            this.initObserve();
            this.initWishlistCompare();
        },

        initActiveItems: function () {
            var layerActives = this.options.active,
                actives = [];

            if (typeof window.layerActiveTabs !== 'undefined') {
                layerActives = window.layerActiveTabs;
            }
            if (layerActives.length) {
                this.element.find('.filter-options-item').each(function (index) {
                    if (~$.inArray($(this).attr('attribute'), layerActives)) {
                        actives.push(index);
                    }
                });
            }

            this.options.active = actives;

            return this;
        },

        initProductListUrl: function () {
            var isProcessToolbar = false;
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
            }
        },

        initObserve: function () {
            var self = this;

            // fix browser back, forward button
            if (typeof window.history.replaceState === "function") {
                window.history.replaceState({url: document.URL}, document.title);

                setTimeout(function () {
                    window.onpopstate = function (e) {
                        if (e.state) {
                            submitFilterAction(e.state.url, 1);
                        }
                    };
                }, 0)
            }

            var pageElements = $('#layer-product-list').find('.pages a');
            pageElements.each(function () {
                var el = $(this),
                    link = self.checkUrl(el.prop('href'));
                if (!link) {
                    return;
                }

                el.bind('click', function (e) {
                    submitFilterAction(link);
                    e.stopPropagation();
                    e.preventDefault();
                })
            });

            var currentElements = this.element.find('.filter-current a, .filter-actions a');
            currentElements.each(function (index) {
                var el = $(this),
                    link = self.checkUrl(el.prop('href'));
                if (!link) {
                    return;
                }

                el.bind('click', function (e) {
                    submitFilterAction(link);
                    e.stopPropagation();
                    e.preventDefault();
                });
            });

            var optionElements = this.element.find('.filter-options a');
            optionElements.each(function (index) {
                var el = $(this),
                    link = self.checkUrl(el.prop('href'));
                if (!link) {
                    return;
                }

                el.bind('click', function (e) {
                    if (el.hasClass('swatch-option-link-layered')) {
                        self.selectSwatchOption(el);
                    }

                    self.ajaxSubmit(link);
                    e.stopPropagation();
                    e.preventDefault();
                });
            });

            var swatchElements = this.element.find('.swatch-attribute');
            swatchElements.each(function (index) {
                var el = $(this);
                var attCode = el.attr('attribute-code');
                if (attCode) {
                    if (self.options.params.hasOwnProperty(attCode)) {
                        var attValues = self.options.params[attCode].split(",");
                        var swatchOptions = el.find('.swatch-option');
                        swatchOptions.each(function (option) {
                            var elOption = $(this);
                            if ($.inArray(elOption.attr('option-id'), attValues) !== -1) {
                                elOption.addClass('selected');
                            }
                        });
                    }
                }
            });
        },

        selectSwatchOption: function (el) {
            var childEl = el.find('.swatch-option');
            if (childEl.hasClass('selected')) {
                childEl.removeClass('selected');
            } else {
                childEl.addClass('selected');
            }
        },

        ajaxSubmit: function (submitUrl) {
            this.element.find(this.options.mobileShopbyElement).trigger('click');

            return submitFilterAction(submitUrl);
        },

        checkUrl: function (url) {
            var regex = /(http|https):\/\/(\w+:{0,1}\w*)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%!\-\/]))?/;

            return regex.test(url) ? url : null;
        },

        //Handling 'add to wishlist' & 'add to compare' event
        initWishlistCompare: function () {
            var isCustomerLoggedIn = this.options.isCustomerLoggedIn,
                elClass = 'a.action.tocompare' + (isCustomerLoggedIn ? ',a.action.towishlist' : '');
            $(elClass).each(function () {
                var el = $(this);
                $(el).bind('click', function (e) {
                    var dataPost = $(el).data('post'),
                        formKey = $('input[name="form_key"]').val(), method = 'post';
                    if (formKey) {
                        dataPost.data.form_key = formKey;
                    }

                    var paramData = $.param(dataPost.data),
                        url = dataPost.action + (paramData.length ? '?' + paramData : '');
                    submitFilterAction(url, true, method);
                    e.stopPropagation();
                    e.preventDefault();
                });
            })
        }
    });

    return $.mageplaza.layer;
});

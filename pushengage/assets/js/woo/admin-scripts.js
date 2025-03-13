jQuery(function ($) {
    'use strict';

    var pe_woocommerce_admin = {
        init: function () {
            pe_woocommerce_admin.bindEvents();
            pe_woocommerce_admin.mountOrderActions();
        },

        bindEvents: function () {
            $('input[type="checkbox"]').each(function () {
                pe_woocommerce_admin.toggleDependentFields($(this));
            }).on('change', function () {
                pe_woocommerce_admin.toggleDependentFields($(this));
            });
        },

        mountOrderActions: function () {
            $('.pe-order-action').on('click', function (e) {
                e.preventDefault();
                var $orderAction = $(this);
                var orderId = $orderAction.data('order-id');
                var orderStatus = $orderAction.data('order-status');
                var notificationAction = $orderAction.data('notification-action');

                pe_woocommerce_admin.handleOrderAction(orderId, orderStatus, notificationAction, $orderAction);
            });
        },

        handleOrderAction: function (orderId, orderStatus, notificationAction, $orderAction) {
            $orderAction.css('pointer-events', 'none');
            $orderAction.closest('.column-pushengage').css('pointer-events', 'none');

            var $dashicon = $orderAction.find('.dashicons');
            var dashiconClass = $dashicon.attr('class');
            $dashicon.attr('class', 'dashicons dashicons-update pe-loading');

            var data = {
                action: 'pe_woocommerce_order_action',
                order_id: orderId,
                order_status: orderStatus,
                notification_action: notificationAction,
                nonce: pushengage_wc_admin.nonce
            };

            $.post(pushengage_wc_admin.ajax_url, data, function (response) {
                if (!response.success) {
                    alert(response.data.message);
                }
                $orderAction.css('pointer-events', 'auto');
                $orderAction.closest('.column-pushengage').css('pointer-events', 'auto');
                $dashicon.attr('class', dashiconClass);
            }, 'json');
        },

        toggleDependentFields: function ($checkbox) {
            var dependentFieldId = $checkbox.attr('id');
            $('[data-depends-on="' + dependentFieldId + '"]').each(function () {
                var $row = $(this).closest('tr');
                if ($row.length) {
                    $row.toggle($checkbox.is(':checked'));
                }
            });
        }
    };

    pe_woocommerce_admin.init();
});

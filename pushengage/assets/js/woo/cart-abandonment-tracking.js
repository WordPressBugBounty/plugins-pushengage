(function ($) {
  const CartAbandonmentTracking = {
    lastCheckoutData: {},
    timer: null,
    isLastCheckoutDataSame(data) {
      return JSON.stringify(this.lastCheckoutData) === JSON.stringify(data);
    },
    fieldIds: {
      billing_email: 'email',
      billing_first_name: 'billing-first_name',
      billing_last_name: 'billing-last_name',
      billing_city: 'billing-city',
      billing_company: 'billing-company',
      billing_country: 'billing-country',
      billing_address_1: 'billing-address_1',
      billing_address_2: 'billing-address_2',
      billing_state: 'billing-state',
      billing_postcode: 'billing-postcode',
      billing_phone: 'billing-phone',
      shipping_first_name: 'shipping-first_name',
      shipping_last_name: 'shipping-last_name',
      shipping_company: 'shipping-company',
      shipping_country: 'shipping-country',
      shipping_address_1: 'shipping-address_1',
      shipping_address_2: 'shipping-address_2',
      shipping_city: 'shipping-city',
      shipping_state: 'shipping-state',
      shipping_postcode: 'shipping-postcode',
      shipping_phone: 'shipping-phone',
    },

    init() {
      var selector = Object.keys(this.fieldIds)
        .map((key) => {
          return '#' + key + ', #' + this.fieldIds[key];
        })
        .join(', ');

      $(document).on('change', selector, function () {
        CartAbandonmentTracking.maybeSendCheckoutData();
      });

      $(document.body).on('updated_checkout', function () {
        CartAbandonmentTracking.maybeSendCheckoutData();
      });

      setTimeout(function () {
        CartAbandonmentTracking.maybeSendCheckoutData();
      }, 1000);
    },

    isValidEmail(value) {
      var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
      return emailRegex.test(value);
    },

    isValidPhone(value) {
      return value.replace(/\D/g, '').trim().length >= 10;
    },

    maybeSendCheckoutData() {
      var email = '';
      var emailField = pushengageCartAbandonmentVars.isBlockCheckout
        ? $('#email')
        : $('#billing_email');
      email = (emailField.val() || '').trim();
      if (!email || !this.isValidEmail(email)) {
        return;
      }

      let billingPhone = '';
      let billingPhoneField = pushengageCartAbandonmentVars.isBlockCheckout
        ? $('#billing-phone')
        : $('#billing_phone');
      billingPhone = (billingPhoneField.val() || '').trim();

      let shippingPhone = '';
      let shippingPhoneField = pushengageCartAbandonmentVars.isBlockCheckout
        ? $('#shipping-phone')
        : $('#shipping_phone');
      shippingPhone = (shippingPhoneField.val() || '').trim();

      if (
        !(billingPhone && this.isValidPhone(billingPhone)) &&
        !(shippingPhone && this.isValidPhone(shippingPhone))
      ) {
        return;
      }

      clearTimeout(this.timer);

      const data = {
        action: 'pushengage_save_cart_abandonment_data',
        nonce: pushengageCartAbandonmentVars.nonce,
      };

      Object.keys(this.fieldIds).forEach((key) => {
        var fieldId = pushengageCartAbandonmentVars.isBlockCheckout
          ? this.fieldIds[key]
          : key;
        var value = ($('#' + fieldId).val() || '').trim();
        if (value) {
          data[key] = value;
        }
      });

      if (
        $(
          '.wc-block-checkout__use-address-for-billing input[type="checkbox"]',
        ).prop('checked')
      ) {
        var billingAndShippingFields = [
          ['billing_first_name', 'shipping_first_name'],
          ['billing_last_name', 'shipping_last_name'],
          ['billing_address_1', 'shipping_address_1'],
          ['billing_address_2', 'shipping_address_2'],
          ['billing_city', 'shipping_city'],
          ['billing_state', 'shipping_state'],
          ['billing_country', 'shipping_country'],
          ['billing_postcode', 'shipping_postcode'],
          ['billing_phone', 'shipping_phone'],
          ['billing_company', 'shipping_company'],
        ];

        billingAndShippingFields.forEach(([billingField, shippingField]) => {
          if (data[shippingField]) {
            data[billingField] = data[shippingField];
          }
        });
      }

      this.timer = setTimeout(function () {
        if (CartAbandonmentTracking.isLastCheckoutDataSame(data)) {
          return;
        }
        $.post(pushengageCartAbandonmentVars.ajaxurl, data).done(
          function (response) {
            if (response.success) {
              CartAbandonmentTracking.lastCheckoutData = data;
            }
          },
        );
      }, 500);
    },
  };
  window.PushEngageWPPluginApp = window.PushEngageWPPluginApp || {};
  window.PushEngageWPPluginApp.CartAbandonmentTracking =
    CartAbandonmentTracking;

  CartAbandonmentTracking.init();
})(jQuery);

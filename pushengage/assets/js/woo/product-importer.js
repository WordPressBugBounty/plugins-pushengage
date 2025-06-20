jQuery(function ($) {
  'use strict';

  var wc_product_importer = {
    init: function () {
      wc_product_importer.addNotificationButton();
      wc_product_importer.bindEvents();
    },

    bindEvents: function () {
      $(document).on(
        'click',
        '#pe-wc-importer-send-notification-btn',
        function (e) {
          e.preventDefault();
          var data = wc_product_importer.validateNotificationFormData();
          if (data) {
            wc_product_importer.sendNotification(data);
          }
        },
      );
    },

    addNotificationButton: function () {
      // Add button inside '.wc-actions'
      var $wcActions = $('.wc-actions');
      var $button = $('<button>', {
        class: 'button button-secondary',
        text: 'Announce New Arrivals',
        css: {
          marginRight: '10px',
        },
      });
      $button.on('click', function () {
        wc_product_importer.showNotificationModal();
      });
      $wcActions.append($button);
    },

    showNotificationModal: function () {
      if (typeof $(this).WCBackboneModal === 'function') {
        $(this).WCBackboneModal({
          template: 'pe-woo-product-importer-modal',
        });
      }
    },

    validateNotificationFormData() {
      let $form = $('#pe-product-import-notification-from');
      let data = {};
      let isValid = true;

      $form.serializeArray().forEach(({ name, value }) => (data[name] = value));

      $form.find('[required]').each(function () {
        let $field = $(this);
        let value = $field.val().trim();
        let maxLength = $field.attr('maxlength');
        let errorMsg =
          $field.data('error-message') || 'This field is required.';

        $field.next('.error-message').remove();
        $field.toggleClass('error', !value);

        if (!value) {
          $field.after(`<p class="error-message">${errorMsg}</p>`);
          isValid = false;
        }

        if (maxLength && value.length > maxLength) {
          $field
            .addClass('error')
            .after(
              `<p class="error-message">The content cannot be longer than ${maxLength} characters.</p>`,
            );
          isValid = false;
        }

        if (
          $field.attr('type') === 'url' &&
          value &&
          !/^https?:\/\//i.test(value)
        ) {
          $field
            .addClass('error')
            .after('<p class="error-message">Please enter a valid URL.</p>');
          isValid = false;
        }
      });

      return isValid ? data : false;
    },

    sendNotification: function (data) {
      var $button = $('#pe-product-import-notification-from');
      var $modal = $button.closest('.wc-backbone-modal');
      var $spinner = $modal.find('.spinner');
      var $errorMessage = $modal.find('.error-message');

      $button.prop('disabled', true);
      $spinner.addClass('is-active');
      $errorMessage.hide();

      var requestData = {
        action: 'pe_woo_send_product_import_notification',
        data: data,
        nonce: pushengage_wc_importer.nonce,
      };

      $.post(pushengage_wc_importer.ajax_url, requestData, function (response) {
        if (response.success) {
          $modal.find('.close').trigger('click');
          alert(response.data.message);
        } else {
          $errorMessage.text(response.data.message).show();
        }
        $button.prop('disabled', false);
        $spinner.removeClass('is-active');
      });
    },
  };

  wc_product_importer.init();
});

/**
 * Handled cart abandonment and timely sync up cart items.
 *
 * @since  4.0.8
 *
 * @returns {void}
 */
jQuery(document).ready(function ($) {
  if (
    typeof peEddCartAbandonment === 'undefined' ||
    typeof peEddCartAbandonment.browseCampaign !== 'string' ||
    typeof peEddCartAbandonment.cartCampaign !== 'string' ||
    typeof peEddCartAbandonment.adminAjax !== 'string' ||
    typeof peEddCartAbandonment._wpnonce !== 'string'
  ) {
    return;
  }

  /**
  * Fire browse abandonment stop event
  *
  * @since  4.0.8
  *
  * @returns {void}
  */
  function peFireBrowseAbandonmentStop() {
    if (!peEddCartAbandonment.browseCampaign) {
      return;
    }
    var storageBrowseDownloadIds = [];

    try {
      storageBrowseDownloadIds = JSON.parse(localStorage.getItem('PeEddBrowseDownloadIds')) || [];
    } catch (e) { }

    // if 'PeEddBrowseDownloadIds' is empty, don't need to fire browse abandonment stop event.
    // because, browse abandonment campaign is not running.
    if (typeof storageBrowseDownloadIds == 'object' && !storageBrowseDownloadIds.length) {
      return;
    }

    var trigger = {
      campaign_name: peEddCartAbandonment.browseCampaign,
      event_name: 'add-to-cart'
    };

    PushEngage.push(function () {
      PushEngage.sendTriggerEvent(trigger)
        .then(function (response) {
          try {
            // reset browse download ids
            localStorage.setItem('PeEddBrowseDownloadIds', JSON.stringify([]));
          } catch (e) { }
        });
    });
  }

  /**
   * Fire cart abandonment
   *
   * @since  4.0.8
   *
   * @param {Object} downloadData
   *
   * @returns {void}
   */
  function peFireCartAbandonment(downloadData) {
    if (
      !downloadData ||
      !downloadData.download_id ||
      !downloadData.download_name ||
      !downloadData.download_price ||
      !downloadData.download_cart_url
    ) {
      return;
    }

    var storageDownloadIds = [];

    try {
      storageDownloadIds = JSON.parse(localStorage.getItem('PeEddCartDownloadIds')) || [];
    } catch (e) { }

    if (
      typeof storageDownloadIds == 'object' &&
      storageDownloadIds.length &&
      storageDownloadIds.indexOf(downloadData.download_id) > -1
    ) {
      return;
    }

    storageDownloadIds.push(downloadData.download_id);

    // fire browse abandonment stop event.
    peFireBrowseAbandonmentStop();

    if (!peEddCartAbandonment.cartCampaign) {
      return;
    }

    var trigger = {
      campaign_name: peEddCartAbandonment.cartCampaign,
      event_name: 'add-to-cart',
      data: {
        productname: downloadData.download_name,
        price: downloadData.download_price,
        notificationurl: downloadData.download_cart_url,
        imageurl: downloadData.download_image || '',
        bigimageurl: downloadData.download_large_image || ''
      }
    };

    // fire cart abandonment start event
    PushEngage.push(function () {
      PushEngage.sendTriggerEvent(trigger)
        .then(function (response) {
          try {
            // update cart download ids.
            localStorage.setItem('PeEddCartDownloadIds', JSON.stringify(storageDownloadIds));
          } catch (e) { }
        })
        .catch(function (error) {
          console.log(error.message, error.details);
        });
    });
  }

  /**
   * If current cart is empty and cart abandonment is running for some products.
   * Then, need to stop it.
   *
   * @since  4.0.8
   *
   * @returns {void}
   */
  function peHandleEmptyCart() {
    var storageDownloadIds = [];

    try {
      storageDownloadIds = JSON.parse(localStorage.getItem('PeEddCartDownloadIds')) || [];
    } catch (e) { }

    // if 'PeEddCartDownloadIds' is empty, don't need to terminate cart abandonment.
    if (typeof storageDownloadIds == 'object' && !storageDownloadIds.length) {
      return;
    }

    var trigger = {
      campaign_name: peEddCartAbandonment.cartCampaign,
      event_name: 'checkout',
    };

    PushEngage.push(function () {
      PushEngage.sendTriggerEvent(trigger)
        .then(function (response) {
          try {
            localStorage.setItem('PeEddCartDownloadIds', JSON.stringify([]));
          } catch (e) { }
        })
        .catch(function (error) {
          console.log(error.message, error.details);
        });
    });
  }

  $('body').on('edd_cart_item_added', function (e, response) {
    var cartHtmlString = response.cart_item;

    if (!cartHtmlString) {
      return;
    }

    var tempElement = $('<div>').html(cartHtmlString);
    var downloadId = '';

    tempElement.find('.edd-remove-from-cart').each(function () {
      downloadId = $(this).data('download-id');
    });

    tempElement.remove();

    if (!downloadId) {
      return;
    }

    jQuery.ajax({
      url: peEddCartAbandonment.adminAjax,
      type: 'POST',
      data: {
        download_id: downloadId,
        action: 'pe_get_edd_download_details',
        _wpnonce: peEddCartAbandonment._wpnonce
      },
      success: function (response) {
        peFireCartAbandonment(response.data || {});
      }
    });
  });

  /**
   * Handled timely sync up of cart abandonment for cart items. It will handle
   * case 1: if instant redirect to checkout page after add to cart
   * case 2: if user has added his own custom button
   * case 3: If someone added to cart but after starting campaign removed item form the cart
   * case 4: if there is any issue in getting 'download-id' from HTML string due to different themes structures.
   * TODO: Now, there is 5 minutes delay but later we will handle these cases in the real time.
   *
   * @since  4.0.8
   *
   * @returns {void}
   */
  function peSyncCartAbandonment() {
    try {
      var currentTime = new Date().getTime();
      var lastSyncUpTime = localStorage.getItem('PeEddCartSyncUpTime') || 0;

      if (Number(lastSyncUpTime) > currentTime) {
        return;
      }

      jQuery.ajax({
        url: peEddCartAbandonment.adminAjax,
        type: 'POST',
        data: {
          action: 'pe_get_edd_cart_items',
          _wpnonce: peEddCartAbandonment._wpnonce
        },
        success: function (response) {
          var cartItems = response.data || [];

          for (var i = 0; i <= cartItems.length; i++) {
            peFireCartAbandonment(cartItems[i]);
          }

          if (!cartItems.length) {
            peHandleEmptyCart();
          }

          // set next sync up time after 3 minutes.
          localStorage.setItem('PeEddCartSyncUpTime', currentTime + 3 * 60 * 1000);
        }
      });
    } catch (e) { }
  }

  /**
   * Handled timely sync up of cart abandonment for cart items.
   *
   * @since  4.0.8
   */
  peSyncCartAbandonment();
});



/**
 * Handled cart abandonment in the case of ajax add to cart and timely sync up cart items.
 *
 * @since  4.0.8
 *
 * @returns {void}
 */
jQuery(document).ready(function ($) {
  if (
    typeof peWcAjaxCartAbandonment === 'undefined' ||
    typeof peWcAjaxCartAbandonment.browseCampaign !== 'string' ||
    typeof peWcAjaxCartAbandonment.cartCampaign !== 'string' ||
    typeof peWcAjaxCartAbandonment.adminAjax !== 'string' ||
    typeof peWcAjaxCartAbandonment._wpnonce !== 'string'
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
    if (!peWcAjaxCartAbandonment.browseCampaign) {
      return;
    }

    var storageBrowseProductIds = [];

    try {
      storageBrowseProductIds = JSON.parse(localStorage.getItem('PeWcBrowseProductIds')) || [];
    } catch (e) { }

    // if 'PeWcBrowseProductIds' is empty, don't need to fire browse abandonment stop event.
    // because, browse abandonment campaign is not running.
    if (typeof storageBrowseProductIds == 'object' && !storageBrowseProductIds.length) {
      return;
    }

    var trigger = {
      campaign_name: peWcAjaxCartAbandonment.browseCampaign,
      event_name: 'add-to-cart'
    };

    PushEngage.push(function () {
      PushEngage.sendTriggerEvent(trigger)
        .then(function (response) {
          try {
            // reset browse product ids
            localStorage.setItem('PeWcBrowseProductIds', JSON.stringify([]));
          } catch (e) { }
        })
        .catch(function (error) {
          console.log(error.message, error.details);
        });
    });
  }

  /**
   * Fire cart abandonment start event
   *
   * @since  4.0.8
   *
   * @param {Object} productData
   *
   * @returns {void}
   */
  function peFireCartAbandonment(productData) {
    if (
      !productData ||
      !productData.product_id ||
      !productData.product_name ||
      !productData.product_price ||
      !productData.product_cart_url
    ) {
      return;
    }

    var storageProductIds = [];

    try {
      storageProductIds = JSON.parse(localStorage.getItem('PeWcCartProductIds')) || [];
    } catch (e) { }

    if (
      typeof storageProductIds == 'object' &&
      storageProductIds.length &&
      storageProductIds.indexOf(productData.product_id) > -1
    ) {
      return;
    }

    storageProductIds.push(productData.product_id);

    // fire browse abandonment stop event.
    peFireBrowseAbandonmentStop();

    if (!peWcAjaxCartAbandonment.cartCampaign) {
      return;
    }

    var trigger = {
      campaign_name: peWcAjaxCartAbandonment.cartCampaign,
      event_name: 'add-to-cart',
      data: {
        productname: productData.product_name,
        price: productData.product_price,
        notificationurl: productData.product_cart_url,
        imageurl: productData.product_image || '',
        bigimageurl: productData.product_large_image || '',
        customername: peWcAjaxCartAbandonment?.customerName || '',
        checkouturl: peWcAjaxCartAbandonment?.checkoutPageUrl || '',
        siteurl: peWcAjaxCartAbandonment?.siteUrl || ''
      }
    };

    // fire cart abandonment start event
    PushEngage.push(function () {
      PushEngage.sendTriggerEvent(trigger)
        .then(function (response) {
          try {
            // update cart product ids.
            localStorage.setItem('PeWcCartProductIds', JSON.stringify(storageProductIds));
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
    var storageProductIds = [];

    try {
      storageProductIds = JSON.parse(localStorage.getItem('PeWcCartProductIds')) || [];
    } catch (e) { }

    // if 'PeWcCartProductIds' is empty, don't need to terminate cart abandonment.
    if (typeof storageProductIds == 'object' && !storageProductIds.length) {
      return;
    }

    var trigger = {
      campaign_name: peWcAjaxCartAbandonment.cartCampaign,
      event_name: 'checkout',
    };

    PushEngage.push(function () {
      PushEngage.sendTriggerEvent(trigger)
        .then(function (response) {
          try {
            localStorage.setItem('PeWcCartProductIds', JSON.stringify([]));
          } catch (e) { }
        })
        .catch(function (error) {
          console.log(error.message, error.details);
        });
    });
  }

  /**
   * Handle add to cart via ajax method in the case of woo commerce specific theme.
   *
   * @since  4.0.8
   *
   * @returns {void}
   */
  $('body').on('added_to_cart', function (event, fragments, cart_hash, $button) {
    var productId = $button.data('product_id');
    if (!productId) {
      return;
    }

    jQuery.ajax({
      url: peWcAjaxCartAbandonment.adminAjax,
      type: 'POST',
      data: {
        product_id: productId,
        action: 'pe_get_wc_product_details',
        _wpnonce: peWcAjaxCartAbandonment._wpnonce
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
   * case 3: If some using block themes then, 'added_to_cart' event will not fire.
   * case 4: If someone added to cart but after starting campaign removed item form the cart
   * case 5: if there is any issue in getting 'product-id' from 'button' HTML string due to different different woo commerce themes structures.
   * TODO: Now, there is 5 minutes delay but later we will handle these cases in the real time.
   *
   * @since  4.0.8
   *
   * @returns {void}
   */
  function peSyncCartAbandonment() {
    try {
      var lastSyncUpTime = localStorage.getItem('PeWcCartSyncUpTime') || 0;
      var currentTime = new Date().getTime();

      if (Number(lastSyncUpTime) > currentTime) {
        return;
      }

      jQuery.ajax({
        url: peWcAjaxCartAbandonment.adminAjax,
        type: 'POST',
        data: {
          action: 'pe_get_wc_cart_items',
          _wpnonce: peWcAjaxCartAbandonment._wpnonce
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
          localStorage.setItem('PeWcCartSyncUpTime', currentTime + 3 * 60 * 1000);
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

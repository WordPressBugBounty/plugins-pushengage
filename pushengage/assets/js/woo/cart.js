/**
 * Handled cart abandonment via hook 'woocommerce_add_to_cart'
 *
 * @since  4.0.8
 *
 * @returns {void}
 */
(function () {
  try {
    if (
      typeof peWcCartAbandonment === 'undefined' ||
      typeof peWcCartAbandonment.browseCampaign !== 'string' ||
      typeof peWcCartAbandonment.cartCampaign !== 'string' ||
      typeof peWcCartAbandonment.productId !== 'string' ||
      typeof peWcCartAbandonment.productName !== 'string' ||
      typeof peWcCartAbandonment.productPrice !== 'string' ||
      typeof peWcCartAbandonment.cartPageUrl !== 'string' ||
      typeof peWcCartAbandonment.checkoutPageUrl !== 'string'
    ) {
      return;
    }

    var storageCartProductIds = [];
    var storageBrowseProductIds = [];

    try {
      storageCartProductIds = JSON.parse(localStorage.getItem('PeWcCartProductIds')) || [];
      storageBrowseProductIds = JSON.parse(localStorage.getItem('PeWcBrowseProductIds')) || [];
    } catch (e) { }

    if (
      typeof storageCartProductIds == 'object' &&
      storageCartProductIds.indexOf(peWcCartAbandonment.productId) > -1
    ) {
      return;
    }

    storageCartProductIds.push(peWcCartAbandonment.productId);


    // fire browse abandonment stop event, only if there is data in 'PeWcBrowseProductIds'
    if (peWcCartAbandonment.browseCampaign && typeof storageBrowseProductIds == 'object' && storageBrowseProductIds.length) {
      var browseTrigger = {
        campaign_name: peWcCartAbandonment.browseCampaign,
        event_name: 'add-to-cart'
      };

      PushEngage.push(function () {
        PushEngage.sendTriggerEvent(browseTrigger)
          .then(function (response) {
            try {
              localStorage.setItem('PeWcBrowseProductIds', JSON.stringify([]));
            } catch (e) { }
          })
          .catch(function (error) {
            console.log(error.message, error.details);
          });
      });
    }

    if (!peWcCartAbandonment.cartCampaign) {
      return;
    }

    var cartTrigger = {
      campaign_name: peWcCartAbandonment.cartCampaign,
      event_name: 'add-to-cart',
      data: {
        productname: peWcCartAbandonment.productName,
        price: peWcCartAbandonment.productPrice,
        notificationurl: peWcCartAbandonment.cartPageUrl,
        imageurl: peWcCartAbandonment.productImage || '',
        bigimageurl: peWcCartAbandonment.productLargeImage || '',
        customername: peWcCartAbandonment?.customerName || '',
        checkouturl: peWcCartAbandonment?.checkoutPageUrl || '',
        siteurl: peWcCartAbandonment?.siteUrl || ''
      }
    };

    // fire cart abandonment start event.
    PushEngage.push(function () {
      PushEngage.sendTriggerEvent(cartTrigger)
        .then(function (response) {
          try {
            //update cart product ids
            localStorage.setItem('PeWcCartProductIds', JSON.stringify(storageCartProductIds));
          } catch (e) { }
        })
        .catch(function (error) {
          console.log(error.message, error.details);
        });
    });
  } catch (e) {
    console.error(e);
  }
})();

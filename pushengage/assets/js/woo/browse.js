/**
 * Handled browse abandonment via hook 'woocommerce_after_single_product'
 *
 * @since  4.0.8
 *
 * @returns {void}
 */
(function () {
  try {
    if (
      typeof peWcBrowseAbandonment === 'undefined' ||
      typeof peWcBrowseAbandonment.browseCampaign !== 'string' ||
      typeof peWcBrowseAbandonment.productId !== 'string' ||
      typeof peWcBrowseAbandonment.productName !== 'string' ||
      typeof peWcBrowseAbandonment.productPrice !== 'string' ||
      typeof peWcBrowseAbandonment.productUrl !== 'string'
    ) {
      return;
    }

    var storageBrowseProductIds = [];
    var storageCartProductIds = [];
    try {
      storageBrowseProductIds = JSON.parse(localStorage.getItem('PeWcBrowseProductIds')) || [];
      storageCartProductIds = JSON.parse(localStorage.getItem('PeWcCartProductIds')) || [];
    } catch (e) { }

    // don't send trigger if product is already added to cart
    if (
      typeof storageCartProductIds == 'object' &&
      storageCartProductIds.length &&
      storageCartProductIds.indexOf(peWcBrowseAbandonment.productId) > -1
    ) {
      return;
    }

    // don't send trigger if browse abandonment is already fired
    if (
      typeof storageBrowseProductIds == 'object' &&
      storageBrowseProductIds.length &&
      storageBrowseProductIds.indexOf(peWcBrowseAbandonment.productId) > -1
    ) {
      return;
    }

    storageBrowseProductIds.push(peWcBrowseAbandonment.productId);

    var trigger = {
      campaign_name: peWcBrowseAbandonment.browseCampaign,
      event_name: 'browse',
      data: {
        productname: peWcBrowseAbandonment.productName,
        price: peWcBrowseAbandonment.productPrice,
        notificationurl: peWcBrowseAbandonment.productUrl,
        imageurl: peWcBrowseAbandonment.productImage || '',
        bigimageurl: peWcBrowseAbandonment.productLargeImage || '',
        customername: peWcBrowseAbandonment?.customerName || '',
        siteurl: peWcBrowseAbandonment?.siteUrl || '',
      }
    };

    PushEngage.push(function () {
      PushEngage.sendTriggerEvent(trigger)
        .then(function (response) {
          try {
            localStorage.setItem('PeWcBrowseProductIds', JSON.stringify(storageBrowseProductIds));
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

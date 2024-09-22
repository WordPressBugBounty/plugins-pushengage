/**
 * Handled checkout event(i.e, cart abandonment stop event) via hook 'woocommerce_thankyou'
 *
 * @since  4.0.8
 *
 * @returns {void}
 */
(function () {
  try {
    if (
      typeof peWcCheckoutEvent === 'undefined' ||
      typeof peWcCheckoutEvent.cartCampaign !== 'string' ||
      typeof peWcCheckoutEvent.orderId !== 'string'
    ) {
      return;
    }

    var storageWcCheckoutOrderIds = [];
    try {
      storageWcCheckoutOrderIds = JSON.parse(localStorage.getItem('PeWcCheckoutOrderIds')) || [];
    } catch (e) {}

    if(
      typeof storageWcCheckoutOrderIds == 'object' &&
      storageWcCheckoutOrderIds.length &&
      storageWcCheckoutOrderIds.indexOf(peWcCheckoutEvent.orderId) > -1
    ) {
      return;
    }

    storageWcCheckoutOrderIds.push(peWcCheckoutEvent.orderId);

    var trigger = {
      campaign_name: peWcCheckoutEvent.cartCampaign,
      event_name: 'checkout',
    };

    if(peWcCheckoutEvent.revenue) {
      trigger.data = {
        revenue: peWcCheckoutEvent.revenue
      };
    }

    PushEngage.push(function() {
      PushEngage.sendTriggerEvent(trigger)
        .then(function(response) {
          try{
            // reset cart product ids and update checkout product ids
            localStorage.setItem('PeWcCheckoutOrderIds', JSON.stringify(storageWcCheckoutOrderIds));
            localStorage.setItem('PeWcCartProductIds', JSON.stringify([]));
          }catch(e){}
        })
        .catch(function(error) {
          console.log(error.message, error.details);
        });
    });

  } catch (e) {
    console.error(e);
  }
})();

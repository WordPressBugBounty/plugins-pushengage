/**
 * Handled checkout event(i.e, cart abandonment stop event) via hook 'edd_order_receipt_before_table'
 *
 * @since  4.0.8
 *
 * @returns {void}
 */
(function () {
  try {
    if (
      typeof peEddCheckoutEvent === 'undefined' ||
      typeof peEddCheckoutEvent.cartCampaign !== 'string' ||
      typeof peEddCheckoutEvent.orderId !== 'string'
    ) {
      return;
    }

    var storageEddCheckoutOrderIds = [];
    try {
      storageEddCheckoutOrderIds = JSON.parse(localStorage.getItem('PeEddCheckoutOrderIds')) || [];
    } catch (e) {}

    if(
      typeof storageEddCheckoutOrderIds == 'object' &&
      storageEddCheckoutOrderIds.length &&
      storageEddCheckoutOrderIds.indexOf(peEddCheckoutEvent.orderId) > -1
    ) {
      return;
    }

    storageEddCheckoutOrderIds.push(peEddCheckoutEvent.orderId);

    var trigger = {
      campaign_name: peEddCheckoutEvent.cartCampaign,
      event_name: 'checkout',
    };

    if(peEddCheckoutEvent.revenue) {
      trigger.data = {
        revenue: peEddCheckoutEvent.revenue
      };
    }

    PushEngage.push(function() {
      PushEngage.sendTriggerEvent(trigger)
        .then(function(response) {
          try{
            // reset cart download ids and update checkout download ids
            localStorage.setItem('PeEddCheckoutOrderIds', JSON.stringify(storageEddCheckoutOrderIds));
            localStorage.setItem('PeEddCartDownloadIds', JSON.stringify([]));
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

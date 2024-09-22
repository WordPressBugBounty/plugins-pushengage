/**
 * Handled browse abandonment via hook 'edd_after_download_content'
 *
 * @since  4.0.8
 *
 * @returns {void}
 */
(function () {
  try {
    if (
      typeof peEddBrowseAbandonment === 'undefined' ||
      typeof peEddBrowseAbandonment.browseCampaign !== 'string' ||
      typeof peEddBrowseAbandonment.downloadId !== 'string' ||
      typeof peEddBrowseAbandonment.downloadName !== 'string' ||
      typeof peEddBrowseAbandonment.downloadPrice !== 'string' ||
      typeof peEddBrowseAbandonment.downloadUrl !== 'string'
    ) {
      return;
    }

    var storageBrowseDownloadIds = [];
    var storageCartDownloadIds = [];
    try {
      storageBrowseDownloadIds = JSON.parse(localStorage.getItem('PeEddBrowseDownloadIds')) || [];
      storageCartDownloadIds = JSON.parse(localStorage.getItem('PeEddCartDownloadIds')) || [];
    } catch (e) { }

    // don't send trigger if download is already added to cart
    if (
      typeof storageCartDownloadIds == 'object' &&
      storageCartDownloadIds.length &&
      storageCartDownloadIds.indexOf(peEddBrowseAbandonment.downloadId) > -1
    ) {
      return;
    }

    // don't send trigger if browse abandonment is already fired
    if (
      typeof storageBrowseDownloadIds == 'object' &&
      storageBrowseDownloadIds.length &&
      storageBrowseDownloadIds.indexOf(peEddBrowseAbandonment.downloadId) > -1
    ) {
      return;
    }

    storageBrowseDownloadIds.push(peEddBrowseAbandonment.downloadId);

    var trigger = {
      campaign_name: peEddBrowseAbandonment.browseCampaign,
      event_name: 'browse',
      data: {
        productname: peEddBrowseAbandonment.downloadName,
        price: peEddBrowseAbandonment.downloadPrice,
        notificationurl: peEddBrowseAbandonment.downloadUrl,
        imageurl: peEddBrowseAbandonment.downloadImage || '',
        bigimageurl: peEddBrowseAbandonment.downloadLargeImage || ''
      }
    };

    PushEngage.push(function () {
      PushEngage.sendTriggerEvent(trigger)
        .then(function (response) {
          try {
            localStorage.setItem('PeEddBrowseDownloadIds', JSON.stringify(storageBrowseDownloadIds));
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

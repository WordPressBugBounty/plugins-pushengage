'use strict';

var PushEngage = window.PushEngage || [];
var PushEngageWPPluginApp = window.PushEngageWPPluginApp || {};
var pushengageSubscriberSync = window.pushengageSubscriberSync || {};

PushEngageWPPluginApp.SubscriberSync =
  PushEngageWPPluginApp.SubscriberSync ||
  (function (w) {
    var subscriberSync = {
      init: function () {
        subscriberSync.getPushEngageSubscriberId(function (subscriberId) {
          subscriberSync.mayBeSyncSubscriberId(subscriberId);
        });

        w.addEventListener('PushEngage.onSubscriptionChange', function (event) {
          subscriberSync.mayBeSyncSubscriberId(event.detail.subscriber_id);
        });

        if ('1' === pushengageSubscriberSync.enabled_leads_segment) {
          subscriberSync.getPushEngageSubscriber(function (subscriber) {
            if (subscriber) {
              subscriberSync.mayBeSyncSegments(subscriber);
            }
          });
        }
      },

      getPushEngageSubscriber: function (cb) {
        PushEngage.push(function () {
          PushEngage.getSubscriber()
            .then(function (subscriber) {
              return cb(subscriber);
            })
            .catch(function () {
              cb(null);
            });
        });
      },

      getPushEngageSubscriberId: function (cb) {
        PushEngage.push(function () {
          PushEngage.getSubscriberId()
            .then(function (subscriberId) {
              return cb(subscriberId);
            })
            .catch(function () {
              cb(null);
            });
        });
      },

      mayBeSyncSegments: function (subscriber) {
        if (subscriber) {
          var segments = subscriber.segments || [];
          var alreadySegmented = segments.some(function (segment) {
            return segment === 'Leads' || segment === 'Customers';
          });

          if (!alreadySegmented) {
            PushEngage.addSegment('Leads').catch(subscriberSync.noop);
          }
        }
      },

      mayBeSyncSubscriberId: function (subscriberId) {
        var localSynchedId = localStorage.getItem('pe_wp_synched_sid');
        var synched_subscriber_ids =
          pushengageSubscriberSync.subscriber_ids || [];
        // if synched_subscriber_ids is an object, convert it to an array.
        if (typeof synched_subscriber_ids === 'object') {
          synched_subscriber_ids = Object.values(synched_subscriber_ids);
        }
        var remove_id = null;
        var add_id = null;

        if (subscriberId) {
          if (!synched_subscriber_ids.includes(subscriberId)) {
            add_id = subscriberId;
          }

          if (localSynchedId) {
            if (localSynchedId !== subscriberId) {
              localStorage.setItem('pe_wp_synched_sid', subscriberId);
              if (synched_subscriber_ids.includes(localSynchedId)) {
                remove_id = localSynchedId;
              }
            }
          } else {
            localStorage.setItem('pe_wp_synched_sid', subscriberId);
          }
        } else {
          if (localSynchedId) {
            if (synched_subscriber_ids.includes(localSynchedId)) {
              remove_id = localSynchedId;
            }
            localStorage.removeItem('pe_wp_synched_sid');
          }
        }

        if (!add_id && !remove_id) {
          return;
        }

        // send a AJAX request to add or remove subscriber id.
        var formData = new FormData();
        formData.append('nonce', pushengageSubscriberSync.nonce);
        formData.append('action', 'pe_subscriber_sync');
        if (add_id) {
          formData.append('add_id', add_id);
        }
        if (remove_id) {
          formData.append('remove_id', remove_id);
        }

        var options = {
          method: 'POST',
          cache: 'no-cache',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams(formData),
        };

        fetch(pushengageSubscriberSync.ajaxUrl, options)
          .then(function (res) {
            return res.json();
          })
          .then(function (result) {
            if (!result.success) {
              localStorage.removeItem('pe_wp_synched_sid');
            }
          })
          .catch(function () {
            localStorage.removeItem('pe_wp_synched_sid');
          });
      },
    };

    return subscriberSync;
  })(window);

// Initialize.
PushEngageWPPluginApp.SubscriberSync.init();

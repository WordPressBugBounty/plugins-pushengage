"use strict";

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

        w.addEventListener("PushEngage.onSubscriptionChange", function (event) {
          subscriberSync.mayBeSyncSubscriberId(event.detail.subscriber_id);
        });
      },

      getPushEngageSubscriberId: function (cb) {
        PushEngage.push(function () {
          PushEngage.getSubscriberId()
            .then(function (subscriberId) {
              return cb(subscriberId);
            })
            .catch(function (error) {
              console.error(error);
            });
        });
      },

      mayBeSyncSubscriberId: function (subscriberId) {
        var localSynchedId = localStorage.getItem("pe_wp_synched_sid");
        var synched_subscriber_ids =
          pushengageSubscriberSync.subscriber_ids || [];
        var remove_id = null;
        var add_id = null;

        if (subscriberId) {
          if (!synched_subscriber_ids.includes(subscriberId)) {
            add_id = subscriberId;
          }

          if (localSynchedId) {
            if (localSynchedId !== subscriberId) {
              localStorage.setItem("pe_wp_synched_sid", subscriberId);
              if (synched_subscriber_ids.includes(localSynchedId)) {
                remove_id = localSynchedId;
              }
            }
          } else {
            localStorage.setItem("pe_wp_synched_sid", subscriberId);
          }
        } else {
          if (localSynchedId) {
            if (synched_subscriber_ids.includes(localSynchedId)) {
              remove_id = localSynchedId;
            }
            localStorage.removeItem("pe_wp_synched_sid");
          }
        }

        if (!add_id && !remove_id) {
          return;
        }

        // send a AJAX request to add or remove subscriber id.
        var formData = new FormData();
        formData.append("nonce", pushengageSubscriberSync?.nonce);
        formData.append("action", "pe_subscriber_sync");
        if (add_id) {
          formData.append("add_id", add_id);
        }
        if (remove_id) {
          formData.append("remove_id", remove_id);
        }

        var options = {
          method: "POST",
          cache: "no-cache",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: new URLSearchParams(formData),
        };

        fetch(pushengageSubscriberSync.ajaxUrl, options)
          .then(function (res) {
            return res.json();
          })
          .then(function (result) {
            if (!result.success) {
              localStorage.removeItem("pe_wp_synched_sid");
            }
          })
          .catch(function (error) {
            console.error(error);
            localStorage.removeItem("pe_wp_synched_sid");
          });
      },
    };

    return subscriberSync;
  })(window);

// Initialize.
PushEngageWPPluginApp.SubscriberSync.init();

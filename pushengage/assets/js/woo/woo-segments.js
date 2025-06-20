'use strict';

var PushEngage = window.PushEngage || [];
var PushEngageWooSegments = window.PushEngageWooSegments || {};
var peWooSegments = window.peWooSegments || {};

PushEngageWooSegments.SyncSegments = (function (w) {
  var wooSegmentsSync = {
    noop: function () {},
    init: function () {
      if ('1' === peWooSegments.enabled_customers_segment) {
        wooSegmentsSync.getPushEngageSubscriber(function (subscriber) {
          if (subscriber) {
            wooSegmentsSync.updateSegments(subscriber);
          }
        });
      }

      var goal = {
        name: 'revenue',
        count: 1,
        value: Number(peWooSegments.order_total) || 1,
      };

      PushEngage.push(function () {
        PushEngage.sendGoal(goal).catch(wooSegmentsSync.noop);
      });
    },

    getPushEngageSubscriber: function (cb) {
      PushEngage.push(function () {
        PushEngage.getSubscriber()
          .then(function (subscriber) {
            cb(subscriber);
          })
          .catch(function () {
            cb(null);
          });
      });
    },

    updateSegments: function (subscriber) {
      if (!subscriber) {
        return;
      }

      var segments = subscriber.segments || [];
      var alreadyInCustomer = segments.some(function (segment) {
        return segment === 'Customers';
      });

      if (!alreadyInCustomer) {
        PushEngage.addSegment('Customers').catch(wooSegmentsSync.noop);
      }

      var alreadyInLeads = segments.some(function (segment) {
        return segment === 'Leads';
      });

      if (alreadyInLeads) {
        PushEngage.removeSegment('Leads').catch(wooSegmentsSync.noop);
      }
    },
  };

  return wooSegmentsSync;
})(window);

// Initialize.
PushEngageWooSegments.SyncSegments.init();

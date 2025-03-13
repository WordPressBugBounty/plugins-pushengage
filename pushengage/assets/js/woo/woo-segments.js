"use strict";

var PushEngage = window.PushEngage || [];
var PushEngageWooSegments = window.PushEngageWooSegments || {};
var peWooSegments = window.peWooSegments || {};

PushEngageWooSegments.SyncSegments = (function (w) {
    var wooSegmentsSync = {
        init: function () {
            if ('1' === peWooSegments?.enabled_customers_segment) {
                wooSegmentsSync.getPushEngageSubscriber(function (subscriber) {
                    if (subscriber) {
                        wooSegmentsSync.updateSegments(subscriber);
                    }
                }
                );
            }

            var goal = {
                "name": "revenue",
                "count": 1,
                "value": Number(peWooSegments?.order_total) || 1,
            };

            PushEngage.push(function () {
                PushEngage.sendGoal(goal)
                    .then(function (response) {
                        console.log(response);
                    })
                    .catch(function (error) {
                        console.log(error.message, error.details);
                    });
            });
        },

        getPushEngageSubscriberId: function (cb) {
            PushEngage.push(function () {
                PushEngage.getSubscriberId()
                    .then(cb)
                    .catch(console.error);
            });
        },

        getPushEngageSubscriber: function (cb) {
            PushEngage.push(function () {
                PushEngage.getSubscriber()
                    .then(function (subscriber) {
                        return cb(subscriber);
                    })
                    .catch(function (error) {
                        console.error(error);
                    });
            });
        },

        updateSegments: function (subscriber) {

            if (!subscriber) {
                return;
            }

            var segments = subscriber.segments;
            var alreadyInCustomer = segments.some(function (segment) {
                return segment === "Customers";
            }
            );

            if (!alreadyInCustomer) {
                try {
                    PushEngage.addSegment('Customers');
                } catch (e) {
                    console.error(e);
                }
            }

            var alreadyInLeads = segments.some(function (segment) {
                return segment === "Leads";
            }
            );

            if (alreadyInLeads) {
                try {
                    PushEngage.removeSegment('Leads');
                } catch (e) {
                    console.error(e);
                }
            }
        }
    };

    return wooSegmentsSync;
})(window);

// Initialize.
PushEngageWooSegments.SyncSegments.init();

'use strict';

var PushEngage = window.PushEngage || [];
var PushEngageWPPluginApp = window.PushEngageWPPluginApp || {};
var pushengageAttributesMetaSync = window.pushengageAttributesMetaSync || {};

PushEngageWPPluginApp.AttributesMetaSync =
  PushEngageWPPluginApp.AttributesMetaSync ||
  (function (w) {
    var mod = {
      init: function () {
        mod.payload = {
          attributes: pushengageAttributesMetaSync.attributes || {},
          userId: pushengageAttributesMetaSync.userId || 0
        };

        if (!mod.payload || !mod.payload.attributes) {
          return;
        }

        if (!w.PushEngage || typeof w.PushEngage.push !== 'function') {
          return;
        }

        w.PushEngage.push(function () {
          mod.evaluateAndMaybeSet();
        });
      },

      evaluateAndMaybeSet: function () {
        var attrs = mod.payload.attributes || {};
        if (!attrs || Object.keys(attrs).length === 0) {
          return;
        }

        try {
          return PushEngage.addAttributes(attrs).catch(function () {
            // Ignore errors.
          });
        } catch (e) {
          // Ignore errors.
        }
      },
    };

    return mod;
  })(window);

// Initialize.
PushEngageWPPluginApp.AttributesMetaSync.init();

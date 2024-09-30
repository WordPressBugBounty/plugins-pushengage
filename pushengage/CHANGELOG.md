# Changelog

All notable changes to the PushEngage plugin will be documented in this file.

### 4.0.10

* Added PushEngage settings in pre-publish checklist.
* Improved onboarding flow.
* Masked the API key on the settings page.
* Fixed an issue where notifications for scheduled posts were not sent when published by third-party plugins.
* Fixed Integrations menu item active state style.

### 4.0.9

* Added new "Trigger" menu to create and manage Triggered Campaigns from plugin.
* Added integration for WooCommerce cart and browse abandonment campaigns.
* Added trigger notifications template for cart and browse abandonment triggers.
* Added support for WooCommerce product categories in audience segments selection.
* Added video library link and video docs links in plugin.
* Optimized data for WPCode integration snippets in PushEngage plugin.

### 4.0.8.1

* Resolved JSON encoding error in multi-byte string processing for enhanced reliability.
* Improved error handling for service-worker registration to ensure seamless functionality.
* Added detailed error messages for troubleshooting in the case of notification sending failure.

### 4.0.8

* Added the option to install the WPCode plugin via PushEngage plugin dashboard.
* Implemented configurable options to trigger browse and cart abandonment campaigns for WooCommerce and EDD through WPCode plugin.
* Added a setting to show/hide PushEngage overview widget in WP Dashboard.
* Updated PushEngage Admin menu label from "Campaigns" to "Push Broadcasts".

### 4.0.7.1

* Removed the PushEngage subscription popup modal from WordPress admin pages.

### 4.0.7

* Added subscription management widget.
* Improved popup modals and widgets preview.
* Improved plugin review notice flow.
* Updated PushEngage sdk init script.

### 4.0.6

* Added Push Broadcast templates.
* Added handling for empty UTM parameters on post editor screen.
* Added checks to prevent double notification sending in the Gutenberg post editor screen.
* Improved default service worker implementation and flow to to fix the issue.
* Fixed special character encoding issue in the notification title and message on post editor screen.
* Fixed empty notification message issue on post editor screen.

### 4.0.5.1

* Fixed performance issue in Post Editor.
* Fixed notification preview style conflict.

### 4.0.5

* Added support for selecting post types for auto push campaigns.
* Added support for custom UTM parameters on the Post Editor screen.
* Added Windows 11 notification preview.
* Added support to test sending notifications on the Post Editor screen.
* Added iOS web notification preview.
* Added notification preview on the Post editor screen.
* Added PushEngage menu in the WordPress admin bar.
* Added PushEngage overview widget to display stats and recent notifications on the WordPress Dashboard.
* Added notice and alert message to collect plugin reviews.
* Added a quick link menu widget inside the PushEngage plugin page.
* Added an email verification warning message in the onboarding flow.
* Added Plan label tag for pro features.
* Added admin notice and alert message to display service worker access permission issues.
* Improved Windows 10 notification preview.
* Improved macOS Chrome notification preview.
* Improved iOS web notification preview.
* Improved PushEngage metabox interface on the Post Editor screen.
* Fixed missing error code in the onboarding error message.
* Changed the default notification title from blog title to post title for auto push campaigns.
* Changed the action button URL to be optional on the Post Editor screen.
* Removed Windows 8 notification preview.

### 4.0.4.1

* Disable quick install option in all popup modals for https site.

### 4.0.4

* Fix the issue of the PushEngage error modal appearing on the Post Editor screen.

### 4.0.3

* Improved onboarding flow with new enhancements.
* Introduced a suggestion for cleaning up inactive subscribers.
* Disabled push notification sending in the post editor if notification limit exceeds the free plan limit.
* Updated the user interface in the campaigns, audience, and analytics sections to handle cases when no data is found.
* Corrected a spelling mistake in certain places.
* Resolved the issue of low tooltip contrast on all pages.
* Removed the countdown timer from the upgrade offer alert.
* Included a support document for Safari settings on the page.
* Enhanced responsiveness of CTA buttons for different screen sizes.
* Added description and help link to popup modals and widgets screen.
* Updated texts and documentation links in all upgrade modals and alerts.
* Blurred quick stats if the site is not properly configured in the dashboard.

### 4.0.2

* Fixed: Spacing conflict in the classic editor.
* Fixed: Allowed pasting longer notification title and message in the post editor.
* Fixed: Resolved PHP warning when the allow_url_fopen directive is disabled.
* Fixed: Corrected CTR calculation for the smart A/B notification.

### 4.0.1

* Resolved an issue regarding PushEngage section in post editor appears to hide other sections.

### 4.0.0

* Added setup wizard process for plugin.
* Enhanced push notification creation flow.
* Autoresponder campaign functionality added.
* Added Analytics for the subscriber, notification, and opt-in data.
* Segment and audience group management tools.
* Improved auto segmentation using WordPress categories.
* Advanced push notification options on the post editor.
* Default and advanced setting management.
* Support for popup modals, widgets, and targeting rules.

### 3.2.3

* Fixed DOING_CRON variable fatal error in PHP version 8.0.

### 3.2.2

* Fixed wp-cli fatal errors in PHP version 8.0.
* Fixed development mode warnings and notices.
* Removed welcome notification UI.

### 3.2.1

* Plugin tested up to wordpress version 5.8.

### 3.2.0

* Added category segmentation feature.

### 3.1.1

* Plugin tested up to wordpress version 5.7.

### 3.1.0

* No longer to add manually service worker for HTTPS website.
* No longer to whitelist manually pushengage script in the WP Rocket.

### 3.0.1

* Stability and performance improvements.

### 3.0.0

* Added url auto segmentation feature.

### 2.0.5

* Fixed some backward compatibility issue.

### 2.0.4

* Fixed script namespace conflict issue.

### 2.0.3

* Fixed big image blur issue.

### 2.0.2

* Fixed scheduled post issue and multiple send issue.

### 2.0

* Security & Performance Fixes.

### 1.5.8

* Improved PushEngage WordPress plugin performance and now, accepting timezone in ISO format.

### 1.5.7

* Updated Readme.txt and user experience of plugin page.

### 1.5.6

* Removed FCM Settings tab. PushEngage now use VAPID for web Push Notifications.

### 1.5.5

* Fixed auto push in edit post page. Now if the page is edited and auto push is enabled, automatically web push notification will be sent to subscribers.

### 1.5.4

* Support PushEngage Notification for all wordpress post types. Now the web push notification will be sent to subscribers, when-ever user publishes any post types.

### 1.5.3

* Support for wordpress 5.0 and 5.0.1. PushEngage web push notification is made adapatable to the new changes from wordpress to improve the customer experience.

### 1.5.2

* Improved performance and fixed UI issues in subscription popup page. The page loading speed has been improved.

### 1.5.1

* Fixed subscripton dailog box issues to improve the subscribers experience and hence the improved subscription rate.

### 1.5.0

* Increased plugin performance. Handled PHP notices and warnings. Support for notification big image. Support for disables subscription popup option on load.

### 1.4.9

* Optimized the plugin and changes for new wordpress version to improve the customer user experience.

### 1.4.8

* Incorporated update site information in general settings tab.

### 1.4.7

* Remove single step optin from http site. Now single step optin is supported only for https sites.

### 1.4.6

* Plugin tested up to WordPress version 4.1.8 to improve the user experience with PushEngage web push notifications.

### 1.4.5

* Incorporated large safari popup with segments, large safari popup and quick install option in subsciption dialogbox tab.

### 1.4.4

* Fixed issues in general settings.

### 1.4.3

* Fixed issues with subscription dailogbox.

### 1.4.2

* Fixed notification title issue while sending post notification.

### 1.4.1

* Fixed slow page loading issue.

### 1.4.0

* Fixed wordpress compatibility issues.

### 1.3.9

* Fixed issue in scheduled notification with segments.

### 1.3.8

* Fixed auto push functionality in general settings section.

### 1.3.7

* Updated new plugin design and functionality.

### 1.3.6

* Improved page speed and API response.

### 1.3.5

* Updated plug-in description and tags

### 1.3.4

* UTM parameter settings are incorporated in general settings.

### 1.3.3

* Fixed special characters issue while sending the notification.

### 1.3.2

* Support for wordpress version 4.7

### 1.3.1

* Fixed issue while posting printing an array.

### 1.3.0

* Require interaction of a notification option is now available in general settings.

### 1.2.9

* Updated require interaction functionality while creating notification.

### 1.2.8

* Fixed display send notification checkbox issue in scheduled notification section.

### 1.2.7

* Fixed issue in save draft checkbox display issue.

### 1.2.6

* Updated dashboard page user interface.

### 1.2.5

* HTTPS installation issues fixed.

### 1.2.4

* Fixed notification issue while saving the post as draft.

### 1.2.3

* Fixed notification issue in New Post section.

### 1.2.2

* Resolved HTTPS issue and fixed minor issues.

### 1.2.1

* Fixed CSS issue. Incorporated advanced options for new notification section(Segments, Scheduled, User Interarction and Expiry)

### 1.2

* This version upgrades the dashboard with several new features.  Also, fixed a CSS Bug. Recommend to upgrade immediately.

### 1.1

* This version fixes some minor issues in plugin. Upgrade immediately.

### 1.0

* Initial release.

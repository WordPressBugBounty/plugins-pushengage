<?php
namespace Pushengage;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

class Uninstaller {

	/**
	 * Delete pushengage options and post metadata
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function plugin_uninstall() {
		global $wpdb;

		// Delete pushengage options.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name IN (
				'pushengage_settings',
				'pe_subscription_plan_type',
				'pe_sent_notifications_count',
				'pe_active_subscribers_count',
				'pe_will_display_review_notice',
				'pe_review_notice_options'
			)
		"
		);

		// Delete pushengage post meta data.
		// Meta key 'pe_push_options' belongs to version 4.0.0 and rest all meta keys belongs to older versions.
		$wpdb->query(
			"DELETE FROM {$wpdb->postmeta} WHERE meta_key IN (
                'pe_push_options',
                'pe_timestamp',
                '_pe_override',
                'pe_override_scheduled',
                'pe_override_scheduled',
                '_pushengage_custom_text',
                '_sedule_notification'
            )"
		);

		/**
		 * Delete pushengage user meta data.
		 *
		 * @since 4.0.7
		 */
		$wpdb->query(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key IN (
				'pushengage_review_notice'
			)"
		);
	}
}

<?php
namespace Pushengage;

use Pushengage\Includes\WPMetricsTracker;

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
				'pe_review_notice_options',
				'pushengage_whatsapp_settings',
				'pushengage_whatsapp_click_to_chat',
				'pushengage_wa_automation_campaigns',
				'pushengage_encryption_key',
				'pe_notifications_row_setting'
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

		/**
		 * Delete pushengage cart abandonment tracker table.
		 *
		 * @since 4.1.2
		 */
		$wpdb->query(
			"DROP TABLE IF EXISTS {$wpdb->prefix}pushengage_wc_abandoned_carts"
		);

		/**
		 * Clear the scheduled hook for abandoned cart notification.
		 *
		 * @since 4.1.2
		 */
		wp_clear_scheduled_hook( 'pushengage_check_abandoned_carts' );

		// Track WP metrics `removed_at` timestamp.
		$metrics_tracker = WPMetricsTracker::get_instance();
		$metrics_tracker->send_metrics(
			array(
				'status'     => 'removed',
				'removed_at' => gmdate(
					'Y-m-d\TH:i:s\Z',
					time()
				),
			)
		);

		/**
		 * Clear the scheduled hook for weekly metrics tracking.
		 *
		 * @since 4.1.4
		 */
		wp_clear_scheduled_hook( 'pushengage_send_weekly_metrics' );
	}

	/**
	 * Trigger immediately after deactivating plugin
	 *
	 * @since 4.1.4
	 *
	 * @return void
	 */
	public static function plugin_deactivate() {
		// Track WP metrics `deactivated_at` timestamp.
		$metrics_tracker = WPMetricsTracker::get_instance();
		$metrics_tracker->send_metrics(
			array(
				'status' => 'inactive',
			)
		);

		// Clear the scheduled hook for weekly metrics tracking.
		wp_clear_scheduled_hook( 'pushengage_send_weekly_metrics' );
	}
}

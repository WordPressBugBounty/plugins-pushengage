<?php

namespace Pushengage\Includes;

use Pushengage\Integrations\WooCommerce\Whatsapp\WhatsappHelper;
use Pushengage\Utils\Options;
use Pushengage\Utils\ArrayHelper;
use Pushengage\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP Metrics Cron Handler.
 *
 * Handles the weekly cron schedule for sending WordPress metrics tracking data.
 *
 * @since 4.1.4
 */
class WPMetricsCron {

	/**
	 * Singleton instance.
	 *
	 * @var WPMetricsCron
	 */
	private static $instance = null;

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Get singleton instance.
	 *
	 * @since 4.1.4
	 * @return WPMetricsCron
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 4.1.4
	 */
	private function __construct() {
		$settings     = Options::get_site_settings();
		$this->logger = Logger::get_instance( 'PushEngage' );

		$metrics_tracking_enabled = ArrayHelper::get( $settings, 'misc.enableWpMetricsTracker', true );

		// Register cron schedule.
		add_filter( 'cron_schedules', array( $this, 'add_weekly_cron_schedule' ) );

		// Register cron event.
		add_action( 'init', array( $this, 'schedule_weekly_metrics_cron' ) );

		if ( $metrics_tracking_enabled ) {
			// Add the cron action to send metrics.
			add_action( 'pushengage_send_weekly_metrics', array( $this, 'send_weekly_metrics' ) );
		}
	}

	/**
	 * Add custom cron schedule for weekly metrics tracking.
	 *
	 * @since 4.1.4
	 * @param array $schedules Existing cron schedules.
	 * @return array
	 */
	public function add_weekly_cron_schedule( $schedules ) {
		$schedules['pushengage_weekly_metrics_interval'] = array(
			'interval' => WEEK_IN_SECONDS,
			'display'  => __( 'Weekly', 'pushengage' ),
		);
		return $schedules;
	}

	/**
	 * Schedule the weekly metrics cron event if not already scheduled.
	 *
	 * @since 4.1.4
	 * @return void
	 */
	public function schedule_weekly_metrics_cron() {
		$settings = Options::get_site_settings();
		$metrics_tracking_enabled = ArrayHelper::get( $settings, 'misc.enableWpMetricsTracker', true );

		if ( $metrics_tracking_enabled ) {
			// Schedule the cron event if not already scheduled.
			if ( ! wp_next_scheduled( 'pushengage_send_weekly_metrics' ) ) {
				wp_schedule_event( time(), 'pushengage_weekly_metrics_interval', 'pushengage_send_weekly_metrics' );
			}
		} else {
			// Remove the scheduled cron if metrics tracking is disabled.
			$this->unschedule_weekly_metrics_cron();
		}
	}

	/**
	 * Unschedule the weekly metrics cron event.
	 *
	 * @since 4.1.4
	 * @return void
	 */
	public function unschedule_weekly_metrics_cron() {
		$timestamp = wp_next_scheduled( 'pushengage_send_weekly_metrics' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'pushengage_send_weekly_metrics' );
		}
	}

	/**
	 * Send weekly metrics tracking data.
	 *
	 * This method is called by the WordPress cron system every week.
	 * It collects various WordPress and server metrics and sends them
	 * to the PushEngage API for tracking purposes.
	 *
	 * To test this manually, you can call this method directly:
	 * WPMetricsCron::get_instance()->send_weekly_metrics();
	 *
	 * @since 4.1.4
	 * @return void
	 */
	public function send_weekly_metrics() {
		try {
			// Check if metrics tracking is enabled.
			$pushengage_settings = Options::get_site_settings();
			$is_enabled          = ArrayHelper::get( $pushengage_settings, 'misc.enableWpMetricsTracker', true );
			if ( ! $is_enabled ) {
				$this->logger->info( 'WP Metrics tracking is disabled, skipping weekly metrics send.' );
				return;
			}

			// Prepare metrics payload.
			$metrics_payload = $this->prepare_weekly_metrics_payload();

			if ( empty( $metrics_payload ) ) {
				return;
			}

			// Send Metrics Data to API.
			$metrics_tracker = WPMetricsTracker::get_instance();
			$response = $metrics_tracker->send_metrics( $metrics_payload );

			if ( ! empty( $response ) ) {
				// Clear WhatsApp metrics data transient after successful send.
				delete_transient( 'pushengage_wp_metrics_whatsapp_tracking' );
			} else {
				$this->logger->warning( 'Failed to send weekly WP metrics - empty response received.' );
			}
		} catch ( \Exception $e ) {
			$this->logger->error( 'Error sending weekly WP metrics: ' . $e->getMessage() );
		}
	}

	/**
	 * Prepare weekly metrics payload.
	 *
	 * @since 4.1.4
	 * @return array
	 */
	private function prepare_weekly_metrics_payload() {
		$pushengage_settings = Options::get_site_settings();
		$api_key             = ArrayHelper::get( $pushengage_settings, 'api_key', null );
		$connected_at        = ArrayHelper::get( $pushengage_settings, 'setup_time', 0 );

		// We use the filemtime of the main.js file to get the installed_at timestamp.
		$installed_at = gmdate( 'Y-m-d\TH:i:s\Z', filemtime( PUSHENGAGE_PLUGIN_PATH . '/dist/static/js/main.js' ) );

		$metrics = array(
			'installed_at' => $installed_at,
		);

		// Setting status based on API key.
		if ( $api_key ) {
			$metrics['status'] = 'connected';
		} else {
			$metrics['status'] = 'disconnected';
		}

		// Add connected_at to the metrics payload if setup_time is not 0.
		if ( 0 !== $connected_at ) {
			$metrics['connected_at'] = gmdate( 'Y-m-d\TH:i:s\Z', $connected_at );
		}

		// Whatsapp specific metrics.
		$whatsapp_settings       = Options::get_whatsapp_settings();
		$whatsapp_transient_data = get_transient( 'pushengage_wp_metrics_whatsapp_tracking' );

		$metrics['data']['whatsapp'] = array(
			'status'      => ! empty( $whatsapp_settings ) && WhatsappHelper::is_valid_whatsapp_credentials( $whatsapp_settings ) ? 'connected' : 'disconnected',
			'automations' => WhatsappHelper::get_automation_campaigns_settings(),
		);

		if ( ! empty( $whatsapp_transient_data ) ) {
			$metrics['data']['whatsapp']['msg_sent']          = absint( $whatsapp_transient_data['message_count'] );
			$metrics['data']['whatsapp']['first_msg_sent_at'] = gmdate( 'Y-m-d\TH:i:s\Z', $whatsapp_transient_data['first_message_ts'] );
			$metrics['data']['whatsapp']['last_msg_sent_at']  = gmdate( 'Y-m-d\TH:i:s\Z', $whatsapp_transient_data['last_message_ts'] );
		}

		// Click to chat specific metrics.
		$click_to_chat_settings = Options::get_whatsapp_click_to_chat_settings();
		$metrics['data']['click_to_chat'] = array(
			'status' => $click_to_chat_settings['enabled'] ? 'enabled' : 'disabled',
		);

		// Push notification automation specific metrics.
		$metrics['data']['push_woo_active_automations'] = Options::get_push_notification_automation_settings();

		// Add WooCommerce specific metrics if WooCommerce is active.
		if ( class_exists( 'WooCommerce' ) && defined( 'WC_VERSION' ) ) {
			$metrics['data']['woo_version'] = WC_VERSION;
		}

		return $metrics;
	}
}

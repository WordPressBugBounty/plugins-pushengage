<?php

namespace Pushengage\Includes;

use Pushengage\Utils\Options;
use Pushengage\Includes\Api\HttpAPI;
use Pushengage\Utils\ArrayHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP Metrics Tracker.
 *
 * @since 4.1.4
 */
class WPMetricsTracker {

	/**
	 * Singleton instance.
	 *
	 * @var WPMetricsTracker
	 */
	private static $instance = null;

	/**
	 * is_enabled.
	 *
	 * @var boolean
	 */
	private $is_enabled = false;

	/**
	 * Default payload data.
	 *
	 * @var array
	 */
	private $payload_base = array();

	/**
	 * Get singleton instance.
	 *
	 * @return WPMetricsTracker
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$pushengage_settings = Options::get_site_settings();
		$site_id             = ArrayHelper::get( $pushengage_settings, 'site_id', null );
		$this->is_enabled    = ArrayHelper::get( $pushengage_settings, 'misc.enableWpMetricsTracker', true );
		$this->payload_base = array(
			'host'           => wp_parse_url( get_site_url(), PHP_URL_HOST ),
			'wp_version'     => get_bloginfo( 'version' ),
			'plugin_version' => PUSHENGAGE_VERSION,
		);

		// Add site_id and status if site_id is not null.
		if ( $site_id ) {
			$this->payload_base['site_id'] = $site_id;
			$this->payload_base['status']  = 'connected';
		}
	}

	/**
	 * Method to send wp metrics to API for tracking.
	 *
	 * @param array $payload Metrics payload array.
	 * @return void
	 */
	public function send_metrics( $payload ) {

		// Check if metrics tracking is enabled.
		if ( ! $this->is_enabled ) {
			return;
		}

		// Prepare payload.
		$payload = array_merge( $this->payload_base, $payload );

		// Send metrics to API.
		$response = HttpAPI::send_unauthenticated_api_request(
			'misc/wp-metrics',
			array(
				'method' => 'POST',
				'body'   => $payload,
			)
		);

		if ( is_wp_error( $response ) ) {
			return;
		}

		if ( 200 !== $response['status'] ) {
			return;
		}

		return $response['data'];
	}
}

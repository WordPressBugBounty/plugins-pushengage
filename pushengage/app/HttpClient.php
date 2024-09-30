<?php
namespace Pushengage;

use Pushengage\Utils\Helpers;
use Pushengage\Utils\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HttpClient {

	const SITES_ACTION_PREFIX = 'sites/';

	/**
	 * Send Api request to external services like pushengage api
	 * to fetch, store, update or delete data.
	 *
	 * @since 4.0.0
	 *
	 * @return array
	 */
	private static function remote_request( $data, $content_type = '' ) {
		$request_url                    = PUSHENGAGE_API_URL . $data['action'];
		$headers['x-pe-api-key']        = $data['api_key'];
		$headers['x-pe-client']         = 'WordPress';
		$headers['x-pe-client-version'] = get_bloginfo( 'version' );
		$headers['x-pe-sdk-version']    = PUSHENGAGE_VERSION;

		if ( ! empty( $content_type ) ) {
			$headers['Content-Type'] = $content_type;
		} else {
			$headers['Content-Type'] = 'application/json';
		}

		$request_args = array(
			'method'     => $data['method'],
			'timeout'    => 10,
			'headers'    => $headers,
			'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . 'Plugin/' . PUSHENGAGE_VERSION . '; ' . get_bloginfo( 'url' ),
		);

		try {
			if ( 'GET' !== $data['method'] ) {
				$request_args['body'] = wp_json_encode( $data['body'], JSON_UNESCAPED_UNICODE );
			}

			$res  = wp_remote_request( esc_url_raw( $request_url ), $request_args );
			$body = wp_remote_retrieve_body( $res );
			return Helpers::json_decode( $body );
		} catch ( \Exception $e ) {
			error_log( 'PushEngage: Failed to send request: ' . $e->getMessage() );
			return array( 'error' => array( 'message' => $e->getMessage() ) );
		}

	}

	/**
	 * Send remote request from pushengage plugin
	 *
	 * @since 4.0.0
	 *
	 * @param array  $remote_data
	 * @param string $content_type
	 *
	 * @return array
	 */
	public static function request( $remote_data, $content_type = '' ) {
		return self::remote_request( $remote_data, $content_type );
	}

	/**
	 * Get Site Info by site id
	 *
	 * @since 4.0.0
	 *
	 * @param string $api_key
	 *
	 * @return array
	 */
	public static function get_site_info( $api_key ) {
		if ( ! $api_key ) {
			return array();
		}

		$request_data = array(
			'api_key' => $api_key,
			'action'  => 'auth',
			'method'  => 'GET',
		);

		$res = self::request( $request_data );
		return isset( $res['data'] ) ? $res['data'] : array();
	}

	/**
	 * Get site settings
	 *
	 * @since 4.0.5
	 *
	 * @param array $name The array containing the name of the settings
	 *
	 * @return array
	 */
	public static function get_site_settings( $names ) {
		$settings = Options::get_site_settings();
		$request_data = array(
			'api_key' => $settings['api_key'],
			'action'  => self::SITES_ACTION_PREFIX . $settings['site_id'] . '/settings?name=' . implode( ',', $names ),
			'method'  => 'GET',
		);

		$res = self::request( $request_data );
		return isset( $res['data'] ) ? $res['data'] : array();
	}

	/**
	 * Get utm params settings
	 *
	 * @since 4.0.0
	 *
	 * @param string $api_key
	 * @param string $site_id
	 *
	 * @return array
	 */
	public static function get_utm_params( $api_key, $site_id ) {
		$request_data = array(
			'api_key' => $api_key,
			'action'  => self::SITES_ACTION_PREFIX . $site_id . '/settings?name=utm_settings',
			'method'  => 'GET',
		);

		$res = self::request( $request_data );
		return isset( $res['data'] ) ? $res['data'] : array();
	}

	/**
	 * Get optin settings
	 *
	 * @since 4.0.4.1
	 *
	 * @param string $api_key
	 * @param string $site_id
	 *
	 * @return array
	 */
	public static function get_optin_settings( $api_key, $site_id ) {
		$request_data = array(
			'api_key' => $api_key,
			'action'  => self::SITES_ACTION_PREFIX . $site_id . '/settings?name=optin_settings',
			'method'  => 'GET',
		);

		$res = self::request( $request_data );
		return isset( $res['data'] ) ? $res['data'] : array();
	}

	/**
	 * Send push campaign
	 *
	 * @since 4.0.0
	 *
	 * @param string $api_key
	 * @param string $site_id
	 * @param object $data
	 * @param Array $metadata
	 *
	 * @return mixed The response from the api server as an array
	 */
	public static function send_push_notification( $api_key, $site_id, $data, $metadata = array() ) {
		$action_url = self::SITES_ACTION_PREFIX . $site_id .
			'/notifications?action=sent&' . http_build_query( $metadata, '', '&', PHP_QUERY_RFC3986 );

		$request_data = array(
			'api_key' => $api_key,
			'action'  => $action_url,
			'method'  => 'POST',
			'body'    => $data,
		);

		return self::request( $request_data );
	}

	/**
	 * update settings
	 *
	 * @since 4.0.0
	 *
	 * @param string $api_key
	 * @param string $site_id
	 * @param object $data
	 *
	 * @return array
	 */
	public static function update_site_settings( $api_key, $site_id, $data ) {
		$request_data = array(
			'api_key' => $api_key,
			'action'  => self::SITES_ACTION_PREFIX . $site_id . '/settings',
			'method'  => 'PUT',
			'body'    => $data,
		);

		$res = self::request( $request_data );
		return isset( $res['data'] ) ? $res['data'] : array();
	}

	/**
	 * Get active subscribers count of currently connected site
	 *
	 * @since 4.0.5
	 *
	 * @return array
	 */
	public static function get_active_subscribers() {
		$settings = Options::get_site_settings();
		$request_data = array(
			'api_key' => $settings['api_key'],
			'action'  => self::SITES_ACTION_PREFIX . $settings['site_id'] . '/subscribers/count/active_subscriber_count',
			'method'  => 'GET',
		);

		$res = self::request( $request_data );
		return isset( $res['data'] ) ? $res['data'] : array();
	}

	/**
	 * Get count of total notifications by site
	 *
	 * @since 4.0.5
	 *
	 *
	 * @return array
	 */
	public static function get_total_sent_notifications_count() {
		$settings = Options::get_site_settings();
		$request_data = array(
			'api_key' => $settings['api_key'],
			'action'  => self::SITES_ACTION_PREFIX . $settings['site_id'] . '/notifications?status=sent&page=1&limit=1',
			'method'  => 'GET',
		);

		$res = self::request( $request_data );
		return isset( $res['data'] ) ? $res['data'] : array();
	}
}

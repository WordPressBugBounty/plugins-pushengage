<?php
namespace Pushengage\Includes\Api;

use Pushengage\Utils\Helpers;
use Pushengage\Utils\Options;
use WP_Error;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HttpAPI {

	/**
	 * The PushEngage private API base URL with trailing slash
	 *
	 * @since 4.0.10
	 * @var string
	 */
	private static $private_api_base_url = PUSHENGAGE_API_URL;

	/**
	 * The PushEngage REST API base URL with trailing slash
	 * @since 4.0.10
	 * @var string
	 */
	private static $rest_api_base_url = PUSHENGAGE_REST_API_URL;


	/**
	 * Returns the API key for the site
	 *
	 * @since 4.0.10
	 *
	 * @return string
	 */
	private static function get_api_key() {
		$settings = Options::get_site_settings();
		return $settings['api_key'];
	}

	/**
	 * Return the user agent string
	 *
	 * @return string
	 */
	private static function get_user_agent() {
		return 'WordPress/' . get_bloginfo( 'version' ) . '; ' . 'Plugin/' . PUSHENGAGE_VERSION . '; ' . get_bloginfo( 'url' );
	}

	/**
	 * Sends http request to the PushEngage private API
	 *
	 * @since 4.0.10
	 * @param string $path The path to the API endpoint
	 * @param array $options {
	 *  Optional. Array of request options
	 *
	 *  @type string $method        The HTTP method to use. Default is 'GET'
	 *  @type array $body           The request body
	 *  @type string $content_type  The content type of the request body.
	 *                              Default is 'application/json'
	 * }
	 *
	 * @return array|WP_Error The response data or WP_Error on failure
	 */
	public static function send_private_api_request( $path, $options = array() ) {
		// Return error if site ID and API keys are missing

		if ( ! Options::has_credentials() ) {
			return new WP_Error( 'no-credentials', __( 'Site not connected. Please make sure to connect your site first.', 'pushengage' ) );
		}

		// Ensure there is exactly one slash between the base URL and the path
		$url = rtrim( self::$private_api_base_url, '/' ) . '/' . ltrim( $path, '/' );

		$headers['x-pe-api-key']        = self::get_api_key();
		$headers['x-pe-client']         = 'WordPress';
		$headers['x-pe-client-version'] = get_bloginfo( 'version' );
		$headers['x-pe-sdk-version']    = PUSHENGAGE_VERSION;

		$args = array(
			'method'     => ! empty( $options['method'] ) ? $options['method'] : 'GET',
			'timeout'    => 10,
			'headers'    => $headers,
			'user-agent' => self::get_user_agent(),
		);

		// If the request method is not GET, set the request body and set the
		// content type header
		try {
			if ( 'GET' !== $args['method'] ) {
				if ( ! empty( $options['content_type'] ) ) {
					$args['headers']['Content-Type'] = $options['content_type'];
					$args['body'] = $options['body'];
				} else {
					$args['headers']['Content-Type'] = 'application/json';
					$args['body'] = wp_json_encode( $options['body'], JSON_UNESCAPED_UNICODE );
				}
			}

			$res  = wp_remote_request( esc_url_raw( $url ), $args );
			if ( is_wp_error( $res ) ) {
				return $res;
			}

			$body = wp_remote_retrieve_body( $res );
			if ( is_wp_error( $res ) ) {
				return $res;
			}

			$data = Helpers::json_decode( $body );
			if ( empty( $data ) ) {
				return new WP_Error( 'invalid-response', __( 'Invalid response from server', 'pushengage' ) );
			}

			if ( ! empty( $data['error'] ) ) {
				return new WP_Error( 'api-error', $data['error']['message'], $data );
			}

			return $data;
		} catch ( \Exception $e ) {
			return new WP_Error( 'api-error', $e->getMessage() );
		}
	}

	/**
	 * Sends http request to the PushEngage Public REST API
	 *
	 * @since 4.0.10
	 * @param string $path The path to the API endpoint
	 * @param array $options {
	 *  Optional. Array of request options
	 *
	 *  @type string $method        The HTTP method to use. Default is 'GET'
	 *  @type array $body           The request body
	 *  @type string $content_type  The content type of the request body.
	 *                              Default is 'application/x-www-form-urlencoded'
	 * }
	 *
	 * @return array|WP_Error The response data or WP_Error on failure
	 */
	public static function send_rest_api_request( $path, $options = array() ) {
		if ( ! Options::has_credentials() ) {
			return new WP_Error( 'no-credentials', __( 'Site not connected. Please make sure to connect your site first.', 'pushengage' ) );
		}

		// Ensure there is exactly one slash between the base URL and the path
		$url = rtrim( self::$rest_api_base_url, '/' ) . '/' . ltrim( $path, '/' );

		$headers['api-key']             = self::get_api_key();
		$headers['x-pe-client']         = 'WordPress';
		$headers['x-pe-client-version'] = get_bloginfo( 'version' );
		$headers['x-pe-sdk-version']    = PUSHENGAGE_VERSION;

		$args = array(
			'method'     => ! empty( $options['method'] ) ? $options['method'] : 'GET',
			'timeout'    => 10,
			'headers'    => $headers,
			'user-agent' => self::get_user_agent(),
		);

		// If the request method is not GET, set the request body and set the
		// content type header
		try {
			if ( 'GET' !== $args['method'] ) {
				if ( ! empty( $options['content_type'] ) ) {
					$args['headers']['Content-Type'] = $options['content_type'];
					$args['body'] = $options['body'];
				} else {
					$args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
					$args['body'] = http_build_query( $options['body'] );
				}
			}

			$res  = wp_remote_request( esc_url_raw( $url ), $args );
			if ( is_wp_error( $res ) ) {
				return $res;
			}

			$body = wp_remote_retrieve_body( $res );
			if ( is_wp_error( $res ) ) {
				return $res;
			}

			$data = Helpers::json_decode( $body );
			if ( empty( $data ) ) {
				return new WP_Error( 'invalid-response', __( 'Invalid response from server', 'pushengage' ) );
			}

			// convert the response to WP_Error if the request was not successful
			if ( false === $data['success'] ) {
				return new WP_Error( 'api-error', $data['message'], $data );
			}

			// convert the success response data from rest api to standard
			// response format used by private api
			$data = array(
				'data' => $data,
			);
			return $data;
		} catch ( \Exception $e ) {
			return new WP_Error( 'api-error', $e->getMessage() );
		}
	}
}

<?php
namespace Pushengage\Includes\Api;

use Pushengage\Logger;
use Pushengage\Utils\Helpers;
use Pushengage\Utils\Options;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Proxy controller forwarding allowlisted Adonis calls from the WP REST API.
 *
 * Used by the post-editor metabox and pre-publish checklist so allowed-role
 * users (and admins) on post screens never receive the PushEngage API key in
 * the browser.
 *
 * @since 4.2.5
 */
class ProxyController {

	const REST_NAMESPACE = 'pushengage/v1';
	const REST_ROUTE     = '/proxy/(?P<path>.+)';
	const MAX_BODY_BYTES = 65536; // 64 KB
	const HTTP_TIMEOUT   = 30;

	/**
	 * Allowlist of `METHOD path` patterns.
	 * `{siteId}` and `{ownerId}` are substituted at request time from server-side options.
	 * `{int}` becomes a numeric segment.
	 *
	 * Any addition here MUST be reviewed for security impact.
	 *
	 * @var string[]
	 */
	const ALLOWLIST = array(
		'GET sites/{siteId}',
		'GET sites/{siteId}/settings',
		'GET sites/{siteId}/app-setting',
		'GET sites/{siteId}/audience-groups',
		'GET sites/{siteId}/audience-groups/{int}',
		'GET sites/{siteId}/subscribers/count/active_subscriber_count',
		'GET sites/{siteId}/automation/rss-feeds',
		'POST sites/{siteId}/generative-ai/text-generation',
		'GET accounts/{ownerId}/fup-usage',
		'GET accounts/{ownerId}/notification-count/total',
		'GET accounts/{ownerId}/credit-usages/credits',
		'GET accounts/{ownerId}/credit-usage-histories/summary',
		'PATCH accounts/{ownerId}/credit-usage-histories/{int}/feedback',
	);

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => array( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ),
				'callback'            => array( $this, 'forward' ),
				'permission_callback' => array( $this, 'can_proxy' ),
				'args'                => array(
					'path' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);
	}

	/**
	 * Permission gate: same rule as the post-editor metabox.
	 * `X-WP-Nonce` is verified by core REST infrastructure.
	 *
	 * @return bool|WP_Error
	 */
	public function can_proxy() {
		return Helpers::user_can_access_post_editor_metabox()
			? true
			: new WP_Error(
				'pushengage_proxy_forbidden',
				__( "You don't have permission to access this PushEngage resource.", 'pushengage' ),
				array( 'status' => 403 )
			);
	}

	/**
	 * Main forwarding handler.
	 */
	public function forward( WP_REST_Request $request ) {
		$method = strtoupper( $request->get_method() );
		$path   = (string) $request->get_param( 'path' );

		// Path normalization & rejection of obviously bad input.
		$path = trim( $path );
		if ( '' === $path ) {
			return new WP_REST_Response( array( 'error' => 'empty_path' ), 400 );
		}
		if ( false !== strpos( $path, '..' ) || false !== strpos( $path, '//' ) || '/' === $path[0] ) {
			return new WP_REST_Response( array( 'error' => 'invalid_path' ), 400 );
		}
		if ( preg_match( '/[^\x21-\x7E]/', $path ) ) {
			return new WP_REST_Response( array( 'error' => 'non_ascii_path' ), 400 );
		}

		$settings = (array) Options::get_site_settings();

		// Allowlist match (uses server-side site_id/owner_id from $settings).
		if ( ! $this->is_allowed( $method, $path, $settings ) ) {
			Logger::get_instance()->warning( sprintf( 'Proxy blocked: %s %s', $method, $path ) );
			return new WP_REST_Response( array( 'error' => 'forbidden_endpoint' ), 403 );
		}

		// Body cap.
		$body = $request->get_body();
		if ( is_string( $body ) && strlen( $body ) > self::MAX_BODY_BYTES ) {
			return new WP_REST_Response( array( 'error' => 'payload_too_large' ), 413 );
		}

		// Build upstream URL.
		$base_url     = rtrim( PUSHENGAGE_API_URL, '/' ) . '/';
		$query_params = $request->get_query_params();
		// `path` is part of the route placeholder; remove it from query if WordPress copied it.
		unset( $query_params['path'] );
		// Strip credential-looking query params (defense in depth: refuse to
		// forward anything a client could use to smuggle auth into Adonis).
		foreach ( array_keys( $query_params ) as $param_name ) {
			if ( preg_match( '/^(api_?key|access_?token|auth|authorization|secret|password|token)$/i', $param_name ) ) {
				unset( $query_params[ $param_name ] );
				continue;
			}
			if ( preg_match( '/_(key|token|secret|password)$/i', $param_name ) ) {
				unset( $query_params[ $param_name ] );
			}
		}
		$upstream_url = $base_url . $path;
		if ( ! empty( $query_params ) ) {
			$upstream_url = add_query_arg( $query_params, $upstream_url );
		}

		// Server-side API key — never trust the browser for this.
		$api_key = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
		if ( empty( $api_key ) ) {
			return new WP_REST_Response( array( 'error' => 'site_not_connected' ), 503 );
		}

		$headers = array(
			'x-pe-api-key'        => $api_key,
			'x-pe-client'         => 'WordPress',
			'x-pe-client-version' => get_bloginfo( 'version' ),
			'x-pe-sdk-version'    => PUSHENGAGE_VERSION,
			'Content-Type'        => 'application/json',
		);

		$args = array(
			'method'  => $method,
			'headers' => $headers,
			'timeout' => self::HTTP_TIMEOUT,
		);
		// Only attach body for verbs that carry one.
		if ( in_array( $method, array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) && is_string( $body ) && '' !== $body ) {
			$args['body'] = $body;
		}

		$response = wp_remote_request( $upstream_url, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response(
				array(
					'error'   => 'upstream_unreachable',
					'message' => $response->get_error_message(),
				),
				504
			);
		}

		$status       = (int) wp_remote_retrieve_response_code( $response );
		$content_type = (string) wp_remote_retrieve_header( $response, 'content-type' );
		$body_text    = (string) wp_remote_retrieve_body( $response );

		$data = null;
		if ( '' !== $body_text ) {
			$decoded = json_decode( $body_text, true );
			if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) {
				// Upstream returned non-JSON (HTML error page, plain text, etc.).
				// Don't leak the raw upstream body to the client. Shape matches
				// TServerAPIErrorResponse so APIError.ts can surface name/status.
				return new WP_REST_Response(
					array(
						'error' => array(
							'name'    => 'upstream_non_json',
							'message' => __( 'Upstream returned a non-JSON response.', 'pushengage' ),
							'status'  => $status,
						),
					),
					$status >= 400 ? $status : 502
				);
			}
			$data = $decoded;
		}

		$rest_response = new WP_REST_Response( $data, $status ? $status : 502 );
		if ( '' !== $content_type && 0 === stripos( $content_type, 'application/json' ) ) {
			$rest_response->header( 'Content-Type', $content_type );
		}
		return $rest_response;
	}

	/**
	 * Materialize allowlist entries with server-side site/owner ids and match.
	 *
	 * @param string $method   HTTP method (already upper-cased).
	 * @param string $path     Normalized request path (no leading slash).
	 * @param array  $settings Site settings blob (from Options::get_site_settings()).
	 * @return bool
	 */
	private function is_allowed( $method, $path, $settings ) {
		$site_id  = isset( $settings['site_id'] ) ? (int) $settings['site_id'] : 0;
		$owner_id = isset( $settings['owner_id'] ) ? (int) $settings['owner_id'] : 0;
		if ( ! $site_id || ! $owner_id ) {
			return false;
		}

		$canonical = $method . ' ' . $path;

		foreach ( self::ALLOWLIST as $entry ) {
			$pattern = str_replace(
				array( '{siteId}', '{ownerId}' ),
				array( (string) $site_id, (string) $owner_id ),
				$entry
			);

			if ( false === strpos( $pattern, '{int}' ) ) {
				if ( $canonical === $pattern ) {
					return true;
				}
				continue;
			}

			// Convert {int} placeholder to regex and anchor.
			$regex = '#^' . str_replace( '\{int\}', '\d+', preg_quote( $pattern, '#' ) ) . '$#';
			if ( preg_match( $regex, $canonical ) ) {
				return true;
			}
		}
		return false;
	}
}

<?php
/**
 * Base class for PushEngage ability registrars.
 *
 * @since 4.2.2
 */

namespace Pushengage\Integrations\Abilities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AbstractRegistrar
 *
 * Provides the shared permission callback, input sanitizer, and a thin
 * `register_ability()` helper that fills PushEngage defaults (category,
 * permission, MCP visibility) so subclasses can focus on schema and behavior.
 *
 * @since 4.2.2
 */
abstract class AbstractRegistrar {

	/**
	 * Permission callback shared across all PushEngage abilities.
	 *
	 * @since 4.2.2
	 * @return bool
	 */
	public static function permission_callback() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Sanitize ability input parameters against a key => type map.
	 *
	 * Missing keys are NOT filled with type defaults — they are simply absent
	 * from the result. Synthesizing a default like `limit => 0` would otherwise
	 * be serialized into outbound query strings as `limit=0`, defeating the
	 * upstream API's own defaults.
	 *
	 * @since 4.2.2
	 * @param array $input  Raw input from the ability.
	 * @param array $schema Map of field key to type (string|date|url|integer|boolean|array|object).
	 * @return array Sanitized input, containing only keys that were present in $input.
	 */
	protected static function sanitize_input( $input, $schema ) {
		$sanitized = array();

		foreach ( $schema as $key => $type ) {
			if ( ! isset( $input[ $key ] ) ) {
				continue;
			}

			switch ( $type ) {
				case 'date':
					$value = sanitize_text_field( $input[ $key ] );
					if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
						$sanitized[ $key ] = $value;
					}
					break;

				case 'url':
					$sanitized[ $key ] = esc_url_raw( $input[ $key ] );
					break;

				case 'integer':
					$sanitized[ $key ] = absint( $input[ $key ] );
					break;

				case 'boolean':
					$sanitized[ $key ] = (bool) $input[ $key ];
					break;

				case 'array':
					$sanitized[ $key ] = is_array( $input[ $key ] )
						? array_map( 'sanitize_text_field', $input[ $key ] )
						: array();
					break;

				case 'object':
					$sanitized[ $key ] = is_array( $input[ $key ] ) ? $input[ $key ] : array();
					break;

				default:
					$sanitized[ $key ] = sanitize_text_field( $input[ $key ] );
					break;
			}
		}

		return $sanitized;
	}

	/**
	 * Unwrap the PushEngage REST envelope to a documented ability shape.
	 *
	 * Upstream private-API responses are wrapped as `{ status, data, user, ... }`
	 * where `user` carries account-level session context (owner email, plan
	 * pricing, Stripe IDs, site list). That envelope must never reach an
	 * ability consumer — abilities are flagged `meta.mcp.public = true`, so a
	 * bridge would otherwise log the operator's billing details on every call.
	 *
	 * @since 4.2.3
	 * @param mixed  $response Raw upstream response (caller must have already
	 *                         handled `is_wp_error`).
	 * @param string $shape    'object' (default) returns the inner `data`
	 *                         payload verbatim. 'rows' coerces `data` to a
	 *                         numeric list of row arrays, dropping anything
	 *                         that isn't an array (e.g. assoc-keyed empties).
	 * @return array
	 */
	protected static function unwrap_envelope( $response, $shape = 'object' ) {
		$data = ( is_array( $response ) && isset( $response['data'] ) && is_array( $response['data'] ) )
			? $response['data']
			: array();

		if ( 'rows' !== $shape ) {
			return $data;
		}

		if ( ! wp_is_numeric_array( $data ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$data,
				static function ( $row ) {
					return is_array( $row );
				}
			)
		);
	}

	/**
	 * Sanitize a WP_Error before returning it from an ability.
	 *
	 * Upstream HTTP errors from `HttpAPI::send_private_api_request` carry the
	 * full REST envelope as `WP_Error` data, including the `user` field with
	 * account-level session context (owner email, plan pricing, Stripe IDs,
	 * site list). MCP bridges that serialize `WP_Error::get_error_data()`
	 * would otherwise leak that envelope through the error channel even after
	 * the success path is locked down by `unwrap_envelope()`.
	 *
	 * The upstream error message is already folded into the WP_Error message
	 * by `HttpAPI::format_upstream_error_message()`, so dropping data is
	 * lossless for the consumer. Non-WP_Error inputs pass through unchanged
	 * so callers can pipe arbitrary returns through this helper.
	 *
	 * Scoped to the ability boundary on purpose — `HttpAPI` is also consumed
	 * by Ajax handlers and cron, which may rely on the current error-data
	 * shape; this helper avoids changing that contract.
	 *
	 * @since 4.2.3
	 * @param mixed $error Possibly a WP_Error instance.
	 * @return mixed Sanitized WP_Error, or the original value untouched.
	 */
	protected static function sanitize_error( $error ) {
		if ( ! is_wp_error( $error ) ) {
			return $error;
		}

		return new \WP_Error( $error->get_error_code(), $error->get_error_message() );
	}

	/**
	 * Register a PushEngage ability with shared defaults applied.
	 *
	 * Defaults filled in: category=pushengage, permission_callback=manage_options,
	 * meta.mcp.public=true, meta.show_in_rest=true. Any default can be overridden
	 * by passing the key in $args.
	 *
	 * Accepts a top-level `annotations` key for convenience and relocates it to
	 * `meta.annotations`, which is where WP_Ability (WP 6.9+) expects it.
	 *
	 * @since 4.2.2
	 * @param string $slug Ability slug (e.g. "pushengage/get-plugin-info").
	 * @param array  $args Ability config; see wp_register_ability() for keys.
	 * @return void
	 */
	protected function register_ability( $slug, array $args ) {
		$meta = array(
			'mcp'          => array( 'public' => true ),
			'show_in_rest' => true,
		);

		if ( isset( $args['annotations'] ) ) {
			$meta['annotations'] = $args['annotations'];
			unset( $args['annotations'] );
		}

		if ( isset( $args['meta'] ) && is_array( $args['meta'] ) ) {
			$meta = array_merge( $meta, $args['meta'] );
		}

		$args['meta'] = $meta;

		$defaults = array(
			'category'            => 'pushengage',
			'permission_callback' => array( __CLASS__, 'permission_callback' ),
		);

		wp_register_ability( $slug, array_merge( $defaults, $args ) );
	}

	/**
	 * Register every ability owned by this registrar.
	 *
	 * @since 4.2.2
	 * @return void
	 */
	abstract public function register();
}

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

<?php

namespace Pushengage\Includes;

use Pushengage\Utils\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Attributes Meta Sync
 *
 * Registers the frontend enqueue hook for syncing mapped user_meta to
 * PushEngage attributes via the Web SDK.
 *
 * @since 4.1.6
 */
class AttributesMetaSync {
	/**
	 * Constructor.
	 *
	 * @since 4.1.6
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_attributes_meta_sync_script' ) );
	}

	/**
	 * Enqueue Attributes → user_meta sync script on the frontend.
	 *
	 *
	 * @since 4.1.6
	 * @return void
	 */
	public function enqueue_attributes_meta_sync_script() {
		// Register the attributes meta sync script with SDK dependency.
		wp_register_script(
			'pushengage-attributes-meta-sync',
			PUSHENGAGE_PLUGIN_URL . 'assets/js/attributes-meta-sync.js',
			array( 'pushengage-sdk-init' ),
			PUSHENGAGE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		// Enqueue only on frontend for logged-in users when site is connected.
		if ( is_admin() ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! \Pushengage\Utils\Options::has_credentials() ) {
			return;
		}

		// Localize resolved attributes and identifiers for the JS script.
		$payload = $this->prepare_localized_payload();

		// If no payload, don't enqueue the script.
		if ( empty( $payload ) ) {
			return;
		}

		wp_localize_script( 'pushengage-attributes-meta-sync', 'pushengageAttributesMetaSync', $payload );

		wp_enqueue_script( 'pushengage-attributes-meta-sync' );
	}

	/**
	 * Get Attribute → WP user_meta mapping from site settings.
	 *
	 * @since 4.1.6
	 *
	 * @return array Mapping in the format attribute_key => user_meta_key.
	 */
	private function get_attribute_user_meta_mapping() {
		$settings = Options::get_site_settings();
		$mapping  = array();

		if ( isset( $settings['attribute_user_meta_mapping'] )
			&& is_array( $settings['attribute_user_meta_mapping'] )
		) {
			foreach ( $settings['attribute_user_meta_mapping'] as $attr_key => $meta_key ) {
				$attr_key = trim( sanitize_text_field( (string) $attr_key ) );
				$meta_key = trim( sanitize_text_field( (string) $meta_key ) );

				if ( '' !== $attr_key && '' !== $meta_key ) {
					$mapping[ $attr_key ] = $meta_key;
				}
			}
		}

		return $mapping;
	}

	/**
	 * Build raw attributes from current user's meta using mapping.
	 *
	 * Casts values to strings, trims whitespace and skips empty values.
	 *
	 * @since 4.1.6
	 *
	 * @return array
	 */
	private function build_raw_attributes() {
		$attributes = array();
		$user_id    = get_current_user_id();

		if ( empty( $user_id ) ) {
			return $attributes;
		}

		$mapping = $this->get_attribute_user_meta_mapping();
		if ( empty( $mapping ) ) {
			return $attributes;
		}

		foreach ( $mapping as $attribute_key => $meta_key ) {
			$value = get_user_meta( $user_id, $meta_key, true );

			if ( is_array( $value ) ) {
				$value = implode( ',', array_filter( array_map( 'strval', $value ) ) );
			} elseif ( is_object( $value ) ) {
				$value = '';
			} elseif ( null === $value ) {
				$value = '';
			} elseif ( is_bool( $value ) ) {
				$value = $value ? '1' : '0';
			} else {
				$value = (string) $value;
			}

			$value = $this->sanitize_attribute_value( $value );
			if ( '' === $value ) {
				continue;
			}

			// Enforce maximum value length (256 chars).
			if ( function_exists( 'mb_substr' ) ) {
				$value = mb_substr( $value, 0, 256, 'UTF-8' );
			} else {
				$value = substr( $value, 0, 256 );
			}

			$attributes[ $attribute_key ] = $value;
		}

		return $attributes;
	}

	/**
	 * Sanitize a meta value for attribute transmission.
	 *
	 * Casts to string, strips tags, normalizes whitespace and removes
	 * control characters. Returns an empty string if nothing remains.
	 *
	 * @since 4.1.6
	 *
	 * @param string $value Raw value.
	 * @return string Sanitized value.
	 */
	private function sanitize_attribute_value( $value ) {
		$value = (string) $value;
		$value = wp_strip_all_tags( $value, true );
		$value = sanitize_text_field( $value );
		$value = trim( preg_replace( '/\s+/u', ' ', $value ) );

		return $value;
	}

	/**
	 * Prepare localized payload for the frontend script.
	 *
	 * @since 4.1.6
	 *
	 * @return array
	 */
	private function prepare_localized_payload() {
		$attributes = $this->build_raw_attributes();
		$user_id    = get_current_user_id();

		if ( empty( $attributes ) || 0 === $user_id ) {
			return array();
		}

		return array(
			'attributes' => $attributes,
			'userId'     => $user_id,
		);
	}
}

<?php
namespace Pushengage\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Options {
	/**
	 * internal cache for site settings option
	 *
	 * @var $array
	 */
	private static $site_settings;

	/**
	 * Get Pushengage Settings Options
	 *
	 * @since 4.0.5
	 *
	 * @return $array
	 */
	public static function get_site_settings() {
		if ( empty( self::$site_settings ) ) {
			$settings = get_option( 'pushengage_settings', array() );

			// Set default values for misc settings if not set
			$defaults = array(
				'hideAdminBarMenu'    => false,
				'hideDashboardWidget' => false,
			);

			foreach ( $defaults as $key => $value ) {
				if ( ! isset( $settings['misc'][ $key ] ) ) {
					$settings['misc'][ $key ] = $value;
				}
			}

			update_option( 'pushengage_settings', $settings );

			self::$site_settings = $settings;
		}

		return self::$site_settings;
	}

	/**
	 * Update pushengage settings Options
	 *
	 * @since 4.0.5
	 *
	 * @return bool
	 */
	public static function update_site_settings( $data ) {
		// clear the internal cache for site settings
		self::$site_settings = array();
		return update_option( 'pushengage_settings', $data );
	}

	/**
	 * Check if site is connected, if so then we have credentials, otherwise false
	 *
	 * @since 4.0.5
	 *
	 * @return boolean
	 */
	public static function has_credentials() {
		$pushengage_settings = self::get_site_settings();
		if (
			! empty( $pushengage_settings['api_key'] )
			&& ! empty( $pushengage_settings['site_id'] )
			&& ! empty( $pushengage_settings['site_key'] )
			&& ! empty( $pushengage_settings['owner_id'] )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Get all the post types which are allowed for auto push
	 *
	 * @since 4.0.5
	 *
	 * @return array
	 */
	public static function get_allowed_post_types_for_auto_push() {
		$pushengage_settings = self::get_site_settings();
		if ( isset( $pushengage_settings['allowed_post_types'] ) ) {
			return json_decode( $pushengage_settings['allowed_post_types'], true );
		}

		$args = array(
			'public' => true,
		);

		return get_post_types( $args );
	}
}

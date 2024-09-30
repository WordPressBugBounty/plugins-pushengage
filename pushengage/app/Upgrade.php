<?php
namespace Pushengage;

use Pushengage\Utils\Options;
use Pushengage\Utils\ArrayHelper;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Upgrade {
	/**
	 * Check and upgrade plugin to latest version
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function plugin_upgrade() {
		$pushengage_settings = Options::get_site_settings();
		if ( ! empty( $pushengage_settings['version'] ) && PUSHENGAGE_VERSION !== $pushengage_settings['version'] ) {
			self::plugin_update( $pushengage_settings );
		}
	}

	/**
	 * update plugin to latest version
	 *
	 * @since 4.0.0
	 *
	 * @param array $pushengage_settings
	 *
	 * @return void
	 */
	private static function plugin_update( $pushengage_settings ) {
		/**
		 * We need to refactor some data of pushengage options.
		 *
		 * @since 4.0.0
		 *
		 */
		if ( version_compare( $pushengage_settings['version'], '4.0.0', '<' ) ) {
			// change key 'appKey' to 'api_key'
			$api_key                        = ArrayHelper::get( $pushengage_settings, 'appKey', '' );
			$pushengage_settings['api_key'] = $api_key;

			// change key 'autoPush' to 'auto_push'
			$pushengage_settings['auto_push'] = ArrayHelper::get( $pushengage_settings, 'autoPush', true );

			// 'use_featured_image' true then, use feature image as notification image
			// 'use_featured_image' false then, use feature image as notification large image and site image as notification icon
			if ( ! empty( $pushengage_settings['use_featured_image'] ) ) {
				$pushengage_settings['notification_icon_type'] = 'featured_image';
			} else {
				$pushengage_settings['notification_icon_type'] = 'site_image';
				$pushengage_settings['featured_large_image']   = true;
			}

			$category_segmentation = self::update_category_segmentation_structure( $pushengage_settings );
			if ( ! empty( $category_segmentation ) ) {
				$pushengage_settings['category_segmentation'] = wp_json_encode( $category_segmentation );
			}

			$site_info = HttpClient::get_site_info( $api_key );

			$site_id  = ArrayHelper::get( $site_info, 'site.site_id', '' );
			$site_key = ArrayHelper::get( $site_info, 'site.site_key', '' );
			$owner_id = ArrayHelper::get( $site_info, 'site.owner_id', '' );

			// if site_id, $site_key or $owner_id is empty return execution and don't let save the 'version'
			// in 'pushengage_settings'. So that, again this function will be called when 'admin_init' hook fires.
			if ( ! $site_id || ! $site_key || ! $owner_id ) {
				return;
			}

			$pushengage_settings['site_id']  = $site_id;
			$pushengage_settings['site_key'] = $site_key;
			$pushengage_settings['owner_id'] = $owner_id;

		}

		$pushengage_settings['version'] = PUSHENGAGE_VERSION;
		// Reset the 'dismissed_whats_new_notice' key to false
		$pushengage_settings['dismissed_whats_new_notice'] = false;

		Options::update_site_settings( $pushengage_settings );
	}

	/**
	 * Update category_segmentation structure in pushengage_settings of options table
	 *
	 * @since 4.0.0
	 *
	 * @param array $pushengage_settings
	 *
	 * @return array $new_category_segmentation_settings
	*/
	public static function update_category_segmentation_structure( $pushengage_settings ) {
		$new_category_segmentation_settings = array();
		$category_segmentation              = ArrayHelper::get( $pushengage_settings, 'category_segmentation', null );

		if ( $category_segmentation ) {
			$category_segmentation_settings = json_decode( $category_segmentation, true );
			$settings                       = ArrayHelper::get( $category_segmentation_settings, 'settings', null );
			if ( $settings && count( $settings ) > 0 ) {
				foreach ( $settings as $setting ) {
					$segment = self::format_segment_mapping( $setting );
					array_push( $new_category_segmentation_settings, $segment );
				}
			}
		}

		return count( $new_category_segmentation_settings ) > 0 ?
			array( 'settings' => $new_category_segmentation_settings ) :
			'';
	}

	/**
	 * Update segment mapping structure
	 *
	 * @since 4.0.0
	 *
	 * @param array $setting
	 *
	 * @return array $segment
	*/
	public static function format_segment_mapping( $setting ) {
		$segment                  = array();
		$segment_id               = intval( $setting['segment_id'] );
		$segment_name             = $setting['segment_name'];
		$segment['category_name'] = $setting['category_name'];
		$segment['segment_id']    = is_array( $segment_id ) ? $segment_id : array( $segment_id );
		$segment['segment_name']  = is_array( $segment_name ) ? $segment_name : array( $segment_name );
		// check if segment id & name is string or numeric,
		// then generate segment_mapping based on segment id & name
		$segment_mapping = ArrayHelper::get( $setting, 'segment_mapping', array() );
		if ( ( is_string( $segment_id ) || is_numeric( $segment_id ) ) && is_string( $segment_name ) ) {
			$segment_mapping[ $segment_id ] = $segment_name;
		}
		$segment['segment_mapping'] = $segment_mapping;

		return $segment;
	}
}

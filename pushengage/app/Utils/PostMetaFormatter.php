<?php
namespace Pushengage\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PostMetaFormatter {
	/**
	 * Sanitize and format and pushengage post metadata from _POST request
	 * for storing in DB
	 *
	 * @since 4.0.5
	 *
	 * @return void
	 */
	public static function format( $data ) {
		$push_options = array();

		$allowed_keys = array(
			'pe_wp_custom_title'   => 'sanitize_text_field',
			'pe_wp_custom_message' => 'sanitize_text_field',
			'pe_wp_big_image'      => 'esc_url_raw',
			'pe_wp_btn1_title'     => 'sanitize_text_field',
			'pe_wp_btn1_url'       => 'esc_url_raw',
			'pe_wp_btn1_image_url' => 'esc_url_raw',
			'pe_wp_btn2_title'     => 'sanitize_text_field',
			'pe_wp_btn2_url'       => 'esc_url_raw',
			'pe_wp_btn2_image_url' => 'esc_url_raw',
		);

		// loop over the array set the value if it is valid
		foreach ( $allowed_keys as $key => $sanitizer ) {
			$val = isset( $data[ $key ] ) ? $sanitizer( wp_unslash( $data[ $key ] ) ) : '';
			if ( ! empty( $val ) ) {
				$push_options[ $key ] = $val;
			}
		}

		if ( empty( $data['pe_wp_btn1_title'] ) ) {
			unset( $push_options['pe_wp_btn1_url'] );
			unset( $push_options['pe_wp_btn1_image_url'] );
		}

		if ( empty( $data['pe_wp_btn2_title'] ) ) {
			unset( $push_options['pe_wp_btn2_url'] );
			unset( $push_options['pe_wp_btn2_image_url'] );
		}

		// audience group
		if ( ! empty( $_POST['pe_wp_audience_group_ids'] ) && is_array( $_POST['pe_wp_audience_group_ids'] ) ) {
			$groups_id = array_values(
				array_filter(
					$_POST['pe_wp_audience_group_ids'],
					function( $val ) {
						$val = intval( $val );
						return $val > 0;
					}
				)
			);
			if ( ! empty( $groups_id ) ) {
				$push_options['pe_wp_audience_group_ids'] = array_map( 'intval', $groups_id );
			}
		}

		// Utm Params
		if ( ! empty( $data['pe_wp_utm_params_enabled'] ) ) {
			$push_options['pe_wp_utm_params_enabled'] = true;
			$utm_keys = array(
				'pe_wp_utm_source',
				'pe_wp_utm_medium',
				'pe_wp_utm_campaign',
				'pe_wp_utm_term',
				'pe_wp_utm_content',
			);

			// loop over the array set the value if it is valid
			foreach ( $utm_keys as $key ) {
				$val = isset( $data[ $key ] ) ? sanitize_text_field( wp_unslash( $data[ $key ] ) ) : '';
				if ( ! empty( $val ) ) {
					$push_options[ $key ] = $val;
				}
			}
		}

		return $push_options;
	}




}

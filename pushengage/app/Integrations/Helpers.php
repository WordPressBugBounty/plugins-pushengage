<?php
namespace Pushengage\Integrations;

use Pushengage\Utils\Helpers as AppHelpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helpers {
	/**
	 * Custom size for getting download image
	 *
	 * @var array
	 */
	private static $large_image_size = array( 364, 180 );

	/**
	 * Returns WooCommerce product details by product id.
	 *
	 * @since 4.0.8
	 *
	 * @param  int $product_id
	 *
	 * @return array
	 */
	public static function get_wc_product_details( $product_id ) {
		$product_details = array();

		if (
			empty( $product_id ) ||
			! class_exists( 'woocommerce' ) ||
			! function_exists( 'wc_get_product' ) ||
			! function_exists( 'wc_get_cart_url' )
		) {
			return $product_details;
		}

		$product_id = intval( $product_id );

		$product = wc_get_product( $product_id );

		if ( empty( $product ) ) {
			return $product_details;
		}

		$product_name = ! empty( $product->get_name() ) ?
			$product->get_name() :
			'';

		$product_price = ! empty( $product->get_price() ) ?
			number_format( intval( $product->get_price() ), 2 ) :
			0;

		$product_url = get_permalink( $product_id );

		$product_cart_url     = wc_get_cart_url();
		$product_checkout_url = wc_get_checkout_url();

		$product_image = AppHelpers::get_post_image( 'thumbnail', $product_id );

		$product_large_image = AppHelpers::get_post_image( self::$large_image_size, $product_id );

		if (
			empty( $product_name ) ||
			empty( $product_price ) ||
			empty( $product_url ) ||
			empty( $product_cart_url ) ||
			empty( $product_checkout_url )
		) {
			return $product_details;
		}

		$product_details = array(
			'product_id'           => esc_html( $product_id ),
			'product_name'         => esc_html( $product_name ),
			'product_price'        => esc_html( $product_price ),
			'product_image'        => esc_url_raw( $product_image ),
			'product_large_image'  => esc_url_raw( $product_large_image ),
			'product_url'          => esc_url_raw( $product_url ),
			'product_cart_url'     => esc_url_raw( $product_cart_url ),
			'product_checkout_url' => esc_url_raw( $product_checkout_url ),
		);

		return $product_details;
	}

	/**
	 * Returns WooCommerce customer details.
	 *
	 * @since 4.0.9
	 *
	 * @return array
	 */
	public static function get_wc_customer_details() {
		$customer_details = array();

		if (
			! class_exists( 'woocommerce' ) ||
			! function_exists( 'wc_get_orders' )
		) {
			return $customer_details;
		}

		global $woocommerce;

		// Check if the user is logged in
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$customer_details = array(
				'user_id'          => $user->ID,
				'username'         => $user->user_login,
				'email'            => $user->user_email,
				'first_name'       => $user->first_name,
				'last_name'        => $user->last_name,
				'billing_address'  => get_user_meta( $user->ID, 'billing_address_1', true ),
				'billing_city'     => get_user_meta( $user->ID, 'billing_city', true ),
				'billing_state'    => get_user_meta( $user->ID, 'billing_state', true ),
				'billing_country'  => get_user_meta( $user->ID, 'billing_country', true ),
				'billing_postcode' => get_user_meta( $user->ID, 'billing_postcode', true ),
			);
		} else {
			$customer_name = $woocommerce->checkout->get_value( 'billing_first_name' );

			if ( ! empty( $customer_name ) ) {
				$customer_details = array(
					'user_id'          => 0,
					'username'         => 'Guest',
					'email'            => $woocommerce->checkout->get_value( 'billing_email' ),
					'first_name'       => $woocommerce->checkout->get_value( 'billing_first_name' ),
					'last_name'        => $woocommerce->checkout->get_value( 'billing_last_name' ),
					'billing_address'  => $woocommerce->checkout->get_value( 'billing_address_1' ),
					'billing_city'     => $woocommerce->checkout->get_value( 'billing_city' ),
					'billing_state'    => $woocommerce->checkout->get_value( 'billing_state' ),
					'billing_country'  => $woocommerce->checkout->get_value( 'billing_country' ),
					'billing_postcode' => $woocommerce->checkout->get_value( 'billing_postcode' ),
				);
			}
		}

		return $customer_details;
	}

	/**
	 * Returns EDD download details by download id.
	 *
	 * @since 4.0.8
	 *
	 * @param  int $download_id
	 *
	 * @return array
	 */
	public static function get_edd_download_details( $download_id ) {
		$download_details = array();

		if ( ! class_exists( 'Easy_Digital_Downloads' )
			|| ! function_exists( 'edd_get_default_variable_price' )
			|| ! function_exists( 'edd_get_download_name' )
			|| ! function_exists( 'edd_has_variable_prices' )
			|| ! function_exists( 'edd_get_price_option_amount' )
			|| ! function_exists( 'edd_get_download_price' )
			|| ! function_exists( 'edd_get_checkout_uri' )
			|| empty( $download_id )
		) {
			return $download_details;
		}

		$default_price_id = edd_get_default_variable_price( $download_id );

		$download_name = ! empty( edd_get_download_name( $download_id, $default_price_id ) ) ?
			edd_get_download_name( $download_id, $default_price_id ) :
			'';

		$download_price = 0;

		if ( edd_has_variable_prices( $download_id ) && ! empty( $default_price_id ) ) {
			$download_price = edd_get_price_option_amount( $download_id, $default_price_id );
		} else {
			$download_price = edd_get_download_price( $download_id );
		}

		$download_price = ! empty( $download_price ) ?
			number_format( intval( $download_price ), 2 ) :
			0;

		$download_url = get_permalink( $download_id );

		$download_image = AppHelpers::get_post_image( 'thumbnail', $download_id );

		$download_large_image = AppHelpers::get_post_image( self::$large_image_size, $download_id );

		$download_cart_url = edd_get_checkout_uri();

		if (
			empty( $download_name ) ||
			empty( $download_price ) ||
			empty( $download_url ) ||
			empty( $download_cart_url )
		) {
			return $download_details;
		}

		$download_details = array(
			'download_name'        => esc_html( $download_name ),
			'download_id'          => esc_html( $download_id ),
			'download_price'       => esc_html( $download_price ),
			'download_image'       => esc_url_raw( $download_image ),
			'download_large_image' => esc_url_raw( $download_large_image ),
			'download_url'         => esc_html( $download_url ),
			'download_cart_url'    => esc_url_raw( $download_cart_url ),
		);

		return $download_details;
	}

	/**
	 * Get WooCommerce product categories by product id.
	 *
	 * @param string|int $product_id
	 * @since 4.0.9
	 * @return array $product_categories.
	 */
	public static function get_wc_product_categories( $product_id ) {
		$product_categories = array();

		if (
			empty( $product_id ) ||
			! class_exists( 'woocommerce' ) ||
			! function_exists( 'wc_get_product' ) ||
			! function_exists( 'wc_get_product_category_list' )
		) {
			return $product_categories;
		}

		$product_id = intval( $product_id );

		$product = wc_get_product( $product_id );

		if ( empty( $product ) || ! method_exists( $product, 'get_category_ids' ) ) {
			return $product_categories;
		}

		$product_categories = $product->get_category_ids();

		return $product_categories;
	}
}

<?php
namespace Pushengage\Integrations;

use Pushengage\Utils\NonceChecker;
use Pushengage\Integrations\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Edd {
	/**
	 * Enqueue JS file to fire EDD browse abandonment campaign
	 *
	 * @since 4.0.8
	 *
	 * @param integer $download_id
	 * @param string $campaign_name
	 *
	 * @return void
	 */
	public static function enqueue_edd_browse_script( $download_id, $campaign_name = '' ) {
		if ( ! class_exists( 'Easy_Digital_Downloads' )
			|| ! is_singular( 'download' )
			|| empty( $download_id )
			|| empty( $campaign_name )
		) {
			return;
		}

		$download_details = Helpers::get_edd_download_details( $download_id );

		if (
			empty( $download_details['download_name'] ) ||
			empty( $download_details['download_price'] ) ||
			empty( $download_details['download_url'] )
		) {
			return;
		}

		wp_enqueue_script(
			'pushengage-edd-browse-abandonment',
			PUSHENGAGE_PLUGIN_URL . 'assets/js/edd/browse.js',
			array( 'pushengage-sdk-init' ),
			PUSHENGAGE_VERSION,
			true
		);

		wp_localize_script(
			'pushengage-edd-browse-abandonment',
			'peEddBrowseAbandonment',
			array(
				'browseCampaign'     => esc_html( $campaign_name ),
				'downloadId'         => esc_html( $download_id ),
				'downloadName'       => esc_html( $download_details['download_name'] ),
				'downloadPrice'      => esc_html( $download_details['download_price'] ),
				'downloadUrl'        => esc_html( $download_details['download_url'] ),
				'downloadImage'      => esc_url_raw( $download_details['download_image'] ),
				'downloadLargeImage' => esc_html( $download_details['download_large_image'] ),
			)
		);
	}

	/**
	 * Enqueue JS file to fire EDD cart abandonment campaign and stop browse abandonment campaign
	 *
	 * @since 4.0.8
	 *
	 * @param string $browse_campaign_name
	 * @param string $cart_campaign_name
	 *
	 * @return void
	 */
	public static function enqueue_edd_cart_abandonment_script( $browse_campaign_name = '', $cart_campaign_name = '' ) {
		if ( empty( $browse_campaign_name ) || empty( $cart_campaign_name ) ) {
			return;
		}

		wp_enqueue_script(
			'pushengage-edd-cart-abandonment',
			PUSHENGAGE_PLUGIN_URL . 'assets/js/edd/cart.js',
			array( 'jquery', 'pushengage-sdk-init' ),
			PUSHENGAGE_VERSION,
			true
		);

		wp_localize_script(
			'pushengage-edd-cart-abandonment',
			'peEddCartAbandonment',
			array(
				'browseCampaign' => esc_html( $browse_campaign_name ),
				'cartCampaign'   => esc_html( $cart_campaign_name ),
				'adminAjax'      => admin_url( 'admin-ajax.php' ),
				'_wpnonce'       => NonceChecker::create_nonce( 'pushengage-edd-cart-abandonment' ),
			)
		);
	}

	/**
	 * Enqueue JS file to fire EDD cart abandonment stop event
	 *
	 * @since 4.0.8
	 *
	 * @param object $order
	 * @param string $campaign_name
	 *
	 * @return void
	 */
	public static function enqueue_edd_checkout_script( $order, $campaign_name = '' ) {
		if (
			empty( $order ) ||
			empty( $order->id ) ||
			empty( $order->total ) ||
			empty( $campaign_name ) ) {
			return;
		}

		$revenue = number_format( intval( $order->total ), 2 );

		wp_enqueue_script(
			'pushengage-edd-checkout',
			PUSHENGAGE_PLUGIN_URL . 'assets/js/edd/checkout.js',
			array( 'pushengage-sdk-init' ),
			PUSHENGAGE_VERSION,
			true
		);

		wp_localize_script(
			'pushengage-edd-checkout',
			'peEddCheckoutEvent',
			array(
				'cartCampaign' => esc_html( $campaign_name ),
				'revenue'      => esc_html( $revenue ),
				'orderId'      => esc_html( $order->id ),
			)
		);
	}
}

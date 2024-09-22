<?php
namespace Pushengage\Integrations;

use Pushengage\Utils\NonceChecker;
use Pushengage\Integrations\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ajax {
	/**
	 * Integrations admin ajax action prefix
	 *
	 * @since 4.0.8
	 *
	 * @var string
	 */
	private $action_prefix = 'wp_ajax_pe_';

	/**
	 * Integrations ajax action prefix
	 *
	 * @since 4.0.8
	 *
	 * @var string
	 */
	private $nopriv_action_prefix = 'wp_ajax_nopriv_pe_';

	/**
	 * Integrations ajax actions list
	 *
	 * @since 4.0.8
	 *
	 * @var array
	 */
	private $actions = array(
		'get_wc_product_details',
		'get_wc_cart_items',
		'get_edd_download_details',
		'get_edd_cart_items',
	);

	/**
	 * Constructor function to register hooks
	 *
	 * @since 4.0.8
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register all integrations ajax hooks
	 *
	 * @since 4.0.8
	 *
	 * @return void
	 */
	private function register_hooks() {
		foreach ( $this->actions as $action ) {
			add_action( $this->action_prefix . $action, array( $this, $action ) );
			add_action( $this->nopriv_action_prefix . $action, array( $this, $action ) );
		}
	}

	/**
	 * Get edd download details by download id.
	 *
	 * @since 4.0.8
	 *
	 * @return Object
	 */
	public function get_edd_download_details() {
		NonceChecker::check( 'pushengage-edd-cart-abandonment' );

		$response = array();

		if (
			! class_exists( 'Easy_Digital_Downloads' ) ||
			empty( $_POST['download_id'] )
		) {
			wp_send_json_success( $response );
		}

		$response = Helpers::get_edd_download_details( $_POST['download_id'] );

		wp_send_json_success( $response );
	}

	/**
	 * Get edd cart downloads.
	 *
	 * @since 4.0.8
	 *
	 * @return Object
	 */
	public function get_edd_cart_items() {
		NonceChecker::check( 'pushengage-edd-cart-abandonment' );

		$response = array();

		if (
			! class_exists( 'Easy_Digital_Downloads' ) ||
			! function_exists( 'edd_get_cart_contents' )
		) {
			wp_send_json_success( $response );
		}

		$cart_items = edd_get_cart_contents();

		if ( empty( $cart_items ) ) {
			wp_send_json_success( $response );
		}

		foreach ( $cart_items as $cart_item ) {
			$download_id = $cart_item['id'];

			if ( empty( $download_id ) ) {
				continue;
			}

			$response[] = Helpers::get_edd_download_details( $download_id );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Get woo commerce product details by product id.
	 *
	 * @since 4.0.8
	 *
	 * @return Object
	 */
	public function get_wc_product_details() {
		NonceChecker::check( 'pushengage-wc-cart-abandonment' );

		$response = array();

		if (
			! class_exists( 'woocommerce' ) ||
			empty( $_POST['product_id'] )
		) {
			wp_send_json_success( $response );
		}

		$response = Helpers::get_wc_product_details( $_POST['product_id'] );

		wp_send_json_success( $response );
	}

	/**
	 * Get woo commerce cart products.
	 *
	 * @since 4.0.8
	 *
	 * @return Object
	 */
	public function get_wc_cart_items() {
		NonceChecker::check( 'pushengage-wc-cart-abandonment' );

		$response = array();

		if (
			! class_exists( 'woocommerce' ) ||
			! function_exists( 'WC' ) ||
			! is_object( WC()->cart )
		) {
			wp_send_json_success( $response );
		}

		$cart = WC()->cart;

		if ( empty( $cart ) ) {
			wp_send_json_success( $response );
		}

		$cart_items = $cart->get_cart();

		if ( empty( $cart_items ) ) {
			wp_send_json_success( $response );
		}

		foreach ( $cart_items as $cart_item ) {
			$product_id = $cart_item['product_id'];

			if ( empty( $product_id ) ) {
				continue;
			}

			$response[] = Helpers::get_wc_product_details( $product_id );
		}

		wp_send_json_success( $response );
	}
}

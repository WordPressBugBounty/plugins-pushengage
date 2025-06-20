<?php

namespace Pushengage\Integrations\WooCommerce\Whatsapp\CartAbandonment;

use Pushengage\Integrations\Helpers;
use Pushengage\Integrations\WooCommerce\Whatsapp\CartAbandonment\Installer;
use Pushengage\Integrations\WooCommerce\Whatsapp\CartAbandonment\Tracker;

/**
 * Cart Abandonment Tracker Class.
 *
 * @since 4.1.2
 */
class CartAbandonmentTracker {
	private $tracker;
	private $installer;
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since 4.1.2
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 4.1.2
	 */
	private function __construct() {
		$this->installer = Installer::get_instance();
		$this->tracker   = Tracker::get_instance();

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialize the cart abandonment tracker.
	 *
	 * @since 4.1.2
	 */
	public function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->installer->install();

		add_action( 'woocommerce_after_checkout_form', array( $this, 'load_cart_abandonment_tracking_script' ) );
		add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_after', array( $this, 'load_cart_abandonment_tracking_script' ) );
	}

	/**
	 * Enqueue the tracker scripts.
	 *
	 * @since 4.1.2
	 * @return void
	 */
	public function load_cart_abandonment_tracking_script() {
		global $post;

		$post_id = isset( $post->ID ) ? intval( $post->ID ) : 0;

		wp_register_script(
			'pushengage-cart-abandonment-tracker',
			PUSHENGAGE_PLUGIN_URL . 'assets/js/woo/cart-abandonment-tracking.js',
			array( 'jquery' ),
			PUSHENGAGE_VERSION,
			true
		);

		$localize_vars = array(
			'ajaxurl'              => admin_url( 'admin-ajax.php' ),
			'nonce'               => wp_create_nonce( 'pushengage_save_cart_abandonment_data' ),
			'isBlockCheckout' => ! empty( $post_id ) && Helpers::is_block_checkout( $post_id ),
		);

		wp_localize_script( 'pushengage-cart-abandonment-tracker', 'pushengageCartAbandonmentVars', $localize_vars );
		wp_enqueue_script( 'pushengage-cart-abandonment-tracker' );
	}
}

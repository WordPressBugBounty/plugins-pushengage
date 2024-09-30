<?php
namespace Pushengage\Integrations;

use Pushengage\Utils\NonceChecker;
use Pushengage\Integrations\Helpers;
use Pushengage\Utils\ArrayHelper;
use Pushengage\Utils\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Woo {

	/**
	 * Initialize WooCommerce integration additional hooks.
	 *
	 * @since 4.0.10
	 * @return void
	 */
	public static function init_hooks() {
		// Admin Ajax Calls
		add_action( 'wp_ajax_pe_dismiss_woo_notice', array( __CLASS__, 'dismiss_woo_notice' ) );
		add_action( 'wp_ajax_pe_get_woo_notice', array( __CLASS__, 'get_woo_notice' ) );
	}
	/**
	 * Integrate browse abandonment trigger for WooCommerce.
	 *
	 * @since 4.0.9
	 * @return void
	 */
	public static function browse_abandonment_trigger() {
		// Bail if WooCommerce is not active.
		if ( ! class_exists( 'woocommerce' ) ) {
			return;
		}

		$pushengage_settings = Options::get_site_settings();

		if ( empty( $pushengage_settings['woo_integration'] ) ) {
			return;
		}

		$enable_browse_abandonment = ArrayHelper::get( $pushengage_settings, 'woo_integration.browse_abandonment.enable', false );
		$browse_campaign_name      = ArrayHelper::get( $pushengage_settings, 'woo_integration.browse_abandonment.name', '' );

		// If browse abandonment is not enabled or campaign name is not set, return.
		if ( empty( $browse_campaign_name ) || empty( $enable_browse_abandonment ) ) {
			return;
		}

		// Trigger browse abandonment function.
		self::enqueue_wc_browse_abandonment_script( $browse_campaign_name );
	}

	/**
	 * Cart abandonment trigger with AJAX add to cart action.
	 *
	 * @since 4.0.9
	 * @return void
	 */
	public static function cart_abandonment_trigger_ajax() {

		// Bail if WooCommerce is not active.
		if ( ! class_exists( 'woocommerce' ) ) {
			return;
		}

		$pushengage_settings = Options::get_site_settings();

		if ( empty( $pushengage_settings['woo_integration'] ) ) {
			return;
		}

		$cart_abandonment_enable = ArrayHelper::get( $pushengage_settings, 'woo_integration.cart_abandonment.enable', false );
		$browse_abandonment_enable = ArrayHelper::get( $pushengage_settings, 'woo_integration.browse_abandonment.enable', false );
		$cart_campaign_name = ArrayHelper::get( $pushengage_settings, 'woo_integration.cart_abandonment.name', '' );
		$browse_campaign_name = ArrayHelper::get( $pushengage_settings, 'woo_integration.browse_abandonment.name', '' );

		// Return if cart abandonment is not enabled.
		if ( empty( $cart_abandonment_enable ) ) {
			$cart_campaign_name = '';
		}

		// Return if browse abandonment is not enabled.
		if ( empty( $browse_abandonment_enable ) ) {
			$browse_campaign_name = '';
		}

		// Check if cart abandonment is not enabled or cart campaign name is not set.
		if ( empty( $cart_abandonment_enable ) || empty( $cart_campaign_name ) ) {
			return;
		}

		// Trigger cart abandonment
		self::enqueue_wc_cart_abandonment_ajax_script( $browse_campaign_name, $cart_campaign_name );
	}

	/**
	 * Handle cart abandonment integration.
	 *
	 * @param string $cart_item_key
	 * @param int $product_id
	 * @since 4.0.9
	 * @return void
	 */
	public static function cart_abandonment_trigger( $cart_item_key, $product_id ) {
		$pushengage_settings = Options::get_site_settings();

		if ( empty( $pushengage_settings['woo_integration'] ) ) {
			return;
		}

		$cart_abandonment_enable = ArrayHelper::get( $pushengage_settings, 'woo_integration.cart_abandonment.enable', false );
		$browse_abandonment_enable = ArrayHelper::get( $pushengage_settings, 'woo_integration.browse_abandonment.enable', false );
		$cart_campaign_name = ArrayHelper::get( $pushengage_settings, 'woo_integration.cart_abandonment.name', '' );
		$browse_campaign_name = ArrayHelper::get( $pushengage_settings, 'woo_integration.browse_abandonment.name', '' );

		// Return if cart abandonment is not enabled.
		if ( empty( $cart_abandonment_enable ) ) {
			$cart_campaign_name = '';
		}

		// Return if browse abandonment is not enabled.
		if ( empty( $browse_abandonment_enable ) ) {
			$browse_campaign_name = '';
		}

		if ( empty( $browse_campaign_name ) && empty( $cart_campaign_name ) ) {
			return;
		}

		// Trigger function for cart abandonment.
		self::enqueue_wc_cart_abandonment_script( $product_id, $browse_campaign_name, $cart_campaign_name );
	}

	/**
	 * Add trigger for cart abandonment checkout.
	 *
	 * @param int $order_id
	 * @since 4.0.9
	 * @return void
	 */
	public static function cart_abandonment_checkout_trigger( $order_id ) {
		// Get options from settings
		$pushengage_settings = Options::get_site_settings();
		if ( isset( $pushengage_settings['woo_integration'] ) && $pushengage_settings['woo_integration']['cart_abandonment']['enable'] ) {
			$cart_campaign_name = $pushengage_settings['woo_integration']['cart_abandonment']['name'];
			if ( empty( $cart_campaign_name ) ) {
				return;
			}
			// Trigger checkout script.
			self::enqueue_wc_checkout_script( $order_id, $cart_campaign_name );
		}
	}
	/**
	 * Enqueue JS file to fire WooCommerce browse abandonment campaign.
	 *
	 * @since 4.0.8
	 *
	 * @param string $campaign_name
	 *
	 * @return void
	 */
	public static function enqueue_wc_browse_abandonment_script( $campaign_name = '' ) {
		if (
		! class_exists( 'woocommerce' ) ||
		! function_exists( 'is_product' ) ||
		! is_product() ||
		isset( $_REQUEST['add-to-cart'] ) ||
		empty( $campaign_name )
		) {
			return;
		}

		$product_id = get_the_ID();

		if ( empty( $product_id ) ) {
			return;
		}

		$product_details  = Helpers::get_wc_product_details( $product_id );
		$customer_details = Helpers::get_wc_customer_details();

		if (
		empty( $product_details['product_name'] ) ||
		empty( $product_details['product_price'] ) ||
		empty( $product_details['product_url'] )
		) {
			return;
		}

		wp_enqueue_script(
			'pushengage-wc-browse-abandonment',
			PUSHENGAGE_PLUGIN_URL . 'assets/js/woo/browse.js',
			array( 'pushengage-sdk-init' ),
			PUSHENGAGE_VERSION,
			true
		);

		$wc_browse_trigger_vars = array(
			'browseCampaign'    => esc_html( $campaign_name ),
			'productId'         => esc_html( $product_id ),
			'productName'       => esc_html( $product_details['product_name'] ),
			'productPrice'      => esc_html( $product_details['product_price'] ),
			'productUrl'        => esc_url_raw( $product_details['product_url'] ),
			'productImage'      => esc_url_raw( $product_details['product_image'] ),
			'productLargeImage' => esc_url_raw( $product_details['product_large_image'] ),
			'siteUrl'           => esc_url_raw( site_url() ),
		);

		if ( ! empty( $customer_details ) ) {
			$wc_browse_trigger_vars['customerName'] = $customer_details['first_name'];
		}

		wp_localize_script(
			'pushengage-wc-browse-abandonment',
			'peWcBrowseAbandonment',
			$wc_browse_trigger_vars
		);
	}

	/**
	 * Enqueue file to fire WooCommerce cart abandonment campaign and stop browse abandonment campaign.
	 *
	 * @since 4.0.8
	 *
	 * @param number $product_id
	 * @param string $browse_campaign_name
	 * @param string $cart_campaign_name
	 *
	 * @return void
	 */
	public static function enqueue_wc_cart_abandonment_script( $product_id, $browse_campaign_name = '', $cart_campaign_name = '' ) {
		if ( ! class_exists( 'woocommerce' ) ||
		! function_exists( 'wc_get_product' ) ||
		! function_exists( 'wc_get_cart_url' ) ||
		empty( $product_id )
		) {
			return;
		}

		// Return if both cart and browse campaign names are empty.
		if ( empty( $browse_campaign_name ) && empty( $cart_campaign_name ) ) {
			return;
		}

		$product_details  = Helpers::get_wc_product_details( $product_id );
		$customer_details = Helpers::get_wc_customer_details();

		if (
		empty( $product_details['product_name'] ) ||
		empty( $product_details['product_price'] ) ||
		empty( $product_details['product_cart_url'] )
		) {
			return;
		}

		wp_enqueue_script(
			'pushengage-wc-cart-abandonment',
			PUSHENGAGE_PLUGIN_URL . 'assets/js/woo/cart.js',
			array( 'pushengage-sdk-init' ),
			PUSHENGAGE_VERSION,
			true
		);

		$wc_cart_trigger_vars = array(
			'browseCampaign'    => esc_html( $browse_campaign_name ),
			'cartCampaign'      => esc_html( $cart_campaign_name ),
			'productId'         => esc_html( $product_id ),
			'productName'       => esc_html( $product_details['product_name'] ),
			'productPrice'      => esc_html( $product_details['product_price'] ),
			'cartPageUrl'       => esc_html( $product_details['product_cart_url'] ),
			'productImage'      => esc_url_raw( $product_details['product_image'] ),
			'productLargeImage' => esc_html( $product_details['product_large_image'] ),
			'checkoutPageUrl'   => esc_html( $product_details['product_checkout_url'] ),
			'siteUrl'           => esc_url_raw( site_url() ),
		);

		if ( ! empty( $customer_details ) ) {
			$wc_cart_trigger_vars['customerName'] = $customer_details['first_name'];
		}

		wp_localize_script(
			'pushengage-wc-cart-abandonment',
			'peWcCartAbandonment',
			$wc_cart_trigger_vars
		);
	}

	/**
	 * Enqueue file to fire WooCommerce cart abandonment campaign and stop browse abandonment campaign,
	 * in the case of add to cart via ajax call etc.
	 *
	 * @since 4.0.8
	 *
	 * @param string $browse_campaign_name
	 * @param string $cart_campaign_name
	 *
	 * @return void
	 */
	public static function enqueue_wc_cart_abandonment_ajax_script( $browse_campaign_name = '', $cart_campaign_name = '' ) {

		// Return if both cart and browse campaign names are empty.
		if ( empty( $browse_campaign_name ) && empty( $cart_campaign_name ) ) {
			return;
		}

		$customer_details = Helpers::get_wc_customer_details();

		wp_enqueue_script(
			'pushengage-wc-ajax-cart-abandonment',
			PUSHENGAGE_PLUGIN_URL . 'assets/js/woo/ajax-cart.js',
			array( 'jquery', 'pushengage-sdk-init' ),
			PUSHENGAGE_VERSION,
			true
		);

		$ajax_cart_vars = array(
			'browseCampaign'  => esc_html( $browse_campaign_name ),
			'cartCampaign'    => esc_html( $cart_campaign_name ),
			'adminAjax'       => admin_url( 'admin-ajax.php' ),
			'_wpnonce'        => NonceChecker::create_nonce( 'pushengage-wc-cart-abandonment' ),
			'siteUrl'         => esc_url_raw( site_url() ),
		);

		if ( function_exists( 'wc_get_checkout_url' ) ) {
			$ajax_cart_vars['checkoutPageUrl'] = esc_url_raw( wc_get_checkout_url() );
		}

		if ( ! empty( $customer_details ) ) {
			$ajax_cart_vars['customerName'] = $customer_details['first_name'];
		}

		wp_localize_script(
			'pushengage-wc-ajax-cart-abandonment',
			'peWcAjaxCartAbandonment',
			$ajax_cart_vars
		);
	}

	/**
	 * Enqueue file to fire WooCommerce cart abandonment stop event
	 *
	 * @since 4.0.8
	 *
	 * @param number $order_id
	 * @param string $campaign_name
	 *
	 * @return void
	 */
	public static function enqueue_wc_checkout_script( $order_id, $campaign_name = '' ) {
		if (
		! class_exists( 'woocommerce' ) ||
		! function_exists( 'wc_get_order' ) ||
		empty( $order_id ) ||
		empty( $campaign_name )
		) {
			return;
		}

		$order = wc_get_order( absint( $order_id ) );

		if ( empty( $order ) ) {
			return;
		}

		$revenue = ! empty( $order->get_total() ) ?
		number_format( intval( $order->get_total() ), 2 ) :
		0;

		wp_enqueue_script(
			'pushengage-wc-checkout',
			PUSHENGAGE_PLUGIN_URL . 'assets/js/woo/checkout.js',
			array( 'pushengage-sdk-init' ),
			PUSHENGAGE_VERSION,
			true
		);

		wp_localize_script(
			'pushengage-wc-checkout',
			'peWcCheckoutEvent',
			array(
				'cartCampaign' => esc_html( $campaign_name ),
				'revenue'      => esc_html( $revenue ),
				'orderId'      => esc_html( $order_id ),
			)
		);
	}

	/**
	 * Should render WooCommerce notice.
	 *
	 * @since 4.0.10
	 * @return bool
	 */
	public static function should_render_woo_notice() {
		// Show notice only if current user can manage_options.
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$settings     = self::get_notice_settings();
		$click_counts = ArrayHelper::get( $settings, 'click_counts', 0 );

		if ( $click_counts >= 3 ) {
			return false;
		}

		// If notice is dismissed show after 30 days.
		$clicked_action = $settings['clicked_action'];
		if ( 'dismissed' === $clicked_action ) {
			$clicked_at = $settings['action_clicked_at'];
			return strtotime( '+30 days', $clicked_at ) < strtotime( 'now' );
		}

		if ( 'later' === $clicked_action ) {
			$clicked_at = $settings['action_clicked_at'];
			return strtotime( '+7 days', $clicked_at ) < strtotime( 'now' );
		}

		return true;
	}

	/**
	 * Get WooCommerce notice settings
	 *
	 * @since 4.0.10
	 * @return array
	 */
	public static function get_notice_settings() {
		$settings = get_user_meta( get_current_user_id(), 'pushengage_woo_notice', true );

		if ( empty( $settings ) ) {
			return array(
				'clicked_action'    => '',
				'action_clicked_at' => 0,
			);
		}

		return $settings;
	}

	/**
	 * Get WooCommerce notice data
	 *
	 * @since 4.0.10
	 * @return void
	 */
	public static function get_woo_notice() {
		NonceChecker::check();

		$notice_data = array(
			'should_render' => self::should_render_woo_notice(),
		);

		wp_send_json_success( $notice_data );
	}

	/**
	 * Update Woo notice settings
	 *
	 * @param array $data
	 * @since 4.0.10
	 * @return void
	 */
	public static function update_notice_info( $data = array() ) {
		$settings     = self::get_notice_settings();
		$click_counts = ArrayHelper::get( $settings, 'click_counts', 0 );

		if ( isset( $data['clicked_action'] ) ) {
			$settings['clicked_action']    = $data['clicked_action'];
			$settings['action_clicked_at'] = time();

			// increment click counts and save.
			$click_counts++;
			$settings['click_counts'] = $click_counts;
		}

		update_user_meta( get_current_user_id(), 'pushengage_woo_notice', $settings );
	}

	/**
	 * Dismiss WooCommerce notice.
	 *
	 * @since 4.0.10
	 * @return void
	 */
	public static function dismiss_woo_notice() {
		NonceChecker::check();

		if ( ! empty( $_POST['clicked_action'] ) ) {
			$data = array();
			$data['clicked_action'] = sanitize_text_field( $_POST['clicked_action'] );
			self::update_notice_info( $data );
		}

		wp_send_json_success();
	}
}

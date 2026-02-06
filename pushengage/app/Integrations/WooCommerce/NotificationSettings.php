<?php
namespace Pushengage\Integrations\WooCommerce;

use Pushengage\Integrations\WooCommerce\NotificationTemplates;
use Pushengage\Utils\ArrayHelper;
use Pushengage\Utils\Options;
use WC_Admin_Settings;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NotificationSettings {
	/**
	 * Init function.
	 *
	 * @since 4.1.0
	 */
	public static function init() {
		// Register a new WooCommerce Settings panel for Push Notifications.
		add_filter( 'woocommerce_settings_tabs_array', array( __CLASS__, 'add_woo_settings_tab' ), 50 );
		add_action( 'woocommerce_settings_pe_notifications', array( __CLASS__, 'push_notifications_settings' ) );

		// Add admin scripts for WooCommerce settings.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ) );

		// Add modal for product importer.
		add_action( 'admin_footer', array( __CLASS__, 'product_importer_modal' ) );

		// Add Notice for Site Not Connected to PushEngage.
		add_action( 'admin_notices', array( __CLASS__, 'site_not_connected_notice' ) );
	}

	/**
	 * Add Notice for Site Not Connected to PushEngage.
	 *
	 * @since 4.1.0
	 */
	public static function site_not_connected_notice() {
		$screen   = get_current_screen();

		$allowed_pages = array(
			'woocommerce_page_wc-orders',
			'woocommerce_page_wc-settings',
		);

		if ( ! in_array( $screen->id, $allowed_pages, true ) ) {
			return;
		}

		$pe_woo_settings_page = isset( $_GET['tab'] ) && 'pe_notifications' === $_GET['tab'] ? true : false;

		if ( ! $pe_woo_settings_page ) {
			return;
		}

		$settings = Options::get_site_settings();
		$api_key  = ArrayHelper::get( $settings, 'api_key', null );

		if ( $api_key ) {
			return;
		}

		?>
		<div class="notice notice-error">
			<p style="font-weight:700">
				<?php esc_html_e( 'You are missing out on features.', 'pushengage' ); ?>
			</p>
			<p>
				<?php
				esc_html_e(
					'Connect your site to PushEngage to start sending push notifications with WooCommerce and recover lost sales.',
					'pushengage'
				);
				?>
			</p>
			<p>
				<a href="<?php echo esc_url( 'admin.php?page=pushengage#/onboarding' ); ?>" class="button-secondary">
					<?php esc_html_e( 'Connect your store now!', 'pushengage' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since 4.1.0
	 */
	public static function admin_scripts() {
		$screen = get_current_screen();

		$allowed_screens = array(
			'woocommerce_page_wc-settings',
			'woocommerce_page_wc-orders',
			// Adding support for Legacy shop order page.
			'edit-shop_order',
		);

		if ( in_array( $screen->id, $allowed_screens, true ) ) {
				wp_register_script(
					'pushengage-wc-admin',
					PUSHENGAGE_PLUGIN_URL . 'assets/js/woo/admin-scripts.js',
					array(),
					PUSHENGAGE_VERSION,
					true
				);
				wp_enqueue_style(
					'pushengage-wc-admin',
					PUSHENGAGE_PLUGIN_URL . 'assets/css/woo/admin-styles.css',
					array(),
					PUSHENGAGE_VERSION
				);
				wp_localize_script(
					'pushengage-wc-admin',
					'pushengage_wc_admin',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'nonce'    => wp_create_nonce( 'pushengage_wc_admin_nonce' ),
					)
				);
				wp_enqueue_script( 'pushengage-wc-admin' );
		}

		if ( 'product_page_product_importer' === $screen->id ) {
			if ( isset( $_GET['step'] ) && 'done' === $_GET['step'] ) {
				wp_enqueue_script(
					'pushengage-wc-importer',
					PUSHENGAGE_PLUGIN_URL . 'assets/js/woo/product-importer.js',
					array( 'jquery', 'wc-backbone-modal' ),
					PUSHENGAGE_VERSION,
					true
				);

				wp_localize_script(
					'pushengage-wc-importer',
					'pushengage_wc_importer',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'nonce'    => wp_create_nonce( 'pushengage_wc_importer_nonce' ),
					)
				);
			}
		}
	}

	/**
	 * Add modal for product importer.
	 *
	 * @since 4.1.0
	 */
	public static function product_importer_modal() {
		$screen = get_current_screen();

		if ( 'product_page_product_importer' === $screen->id ) {
			if ( isset( $_GET['step'] ) && 'done' === $_GET['step'] ) {
				?>
					<script type="text/template" id="tmpl-pe-woo-product-importer-modal">
						<div class="wc-backbone-modal">
							<div class="wc-backbone-modal-content">
								<section class="wc-backbone-modal-main" role="main">
									<header class="wc-backbone-modal-header">
										<h1><?php esc_html_e( 'Send Push Notification', 'pushengage' ); ?></h1>
										<button class="modal-close modal-close-link dashicons dashicons-no-alt">
											<span class="screen-reader-text">Close modal panel</span>
										</button>
									</header>
									<article>
										<form id="pe-product-import-notification-from" action="" method="post">
											<table class="form-table">
												<tbody>
													<tr>
														<th scope="row">
															<label for="pe-woo-product-importer-title"><?php esc_html_e( 'Notification Title', 'pushengage' ); ?>
														</label>
														</th>
														<td class="forminp forminp-text">
															<input type="text" name="notification_title" id="pe-woo-product-importer-title" class="regular-text" value="<?php esc_attr_e( '🌟 Just Arrived!', 'pushengage' ); ?>" required data-error-message="<?php esc_attr_e( 'Notification Title cannot be empty.', 'pushengage' ); ?>" maxlength="85">
														</td>
													</tr>
													<tr>
														<th scope="row">
															<label for="pe-woo-product-importer-message"><?php esc_html_e( 'Notification Message', 'pushengage' ); ?></label>
														</th>
														<td class="forminp forminp-text">
															<textarea name="notification_message" id="pe-woo-product-importer-message" class="regular-text" data-error-message="<?php esc_attr_e( 'Notification Message cannot be empty.', 'pushengage' ); ?>" maxlength="135" required><?php esc_attr_e( 'Exciting news! Check out our latest arrivals and find something new to love.', 'pushengage' ); ?></textarea>
														</td>
													</tr>
													<tr>
														<th scope="row">
															<label for="pe-woo-product-importer-url"><?php esc_html_e( 'Notification URL', 'pushengage' ); ?></label>
														</th>
														<td class="forminp forminp-text">
															<input type="url" name="notification_url" id="pe-woo-product-importer-url" maxlength="1600" class="regular-text" data-error-message="<?php esc_attr_e( 'Notification URL cannot be empty.', 'pushengage' ); ?>" value="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" required>
														</td>
													</tr>
												</tbody>
											</table>
										</form>
									</article>
									<footer>
										<div class="inner">
											<button id="pe-wc-importer-send-notification-btn" class="button button-primary button-large"><?php esc_html_e( 'Send Notification', 'pushengage' ); ?></button>
										</div>
									</footer>
								</section>
							</div>
						</div>
						<div class="wc-backbone-modal-backdrop modal-close"></div>
					</script>
				<?php
			}
		}
	}

	/**
	 * Add WooCommerce settings tab for Push Notifications.
	 *
	 * @param array $settings_tabs Array of existing settings tabs.
	 * @since 4.1.0
	 * @return array $settings_tabs Updated array of settings tabs.
	 */
	public static function add_woo_settings_tab( $settings_tabs ) {
		$settings_tabs['pe_notifications'] = __( 'Push Notifications', 'pushengage' );
		return $settings_tabs;
	}

	/**
	 * Get admin roles.
	 *
	 * @since 4.1.0
	 * @return array $roles Array of admin roles.
	 */
	public static function get_admin_roles() {
		global $wp_roles;

		$allowed_roles = array(
			'administrator' => __( 'Administrator', 'pushengage' ),
			'editor'        => __( 'Editor', 'pushengage' ),
			'shop_manager'  => __( 'Shop Manager', 'pushengage' ),
		);

		$roles = array();

		if ( ! empty( $wp_roles->roles ) ) {
			foreach ( $wp_roles->roles as $role => $role_data ) {
				if ( array_key_exists( $role, $allowed_roles ) ) {
					$roles[ $role ] = $role_data['name'];
				}
			}
		}

		return apply_filters( 'pushengage_woocommerce_notification_admin_roles', $roles );
	}

	/**
	 * Get push notification events.
	 *
	 * @since 4.1.0
	 * @return array $notifications Array of push notification events.
	 */
	public static function get_push_notification_events() {
		$notifications = array(
			'new_order' => array(
				'title'       => __( 'New Order', 'pushengage' ),
				'description' => __( 'Receive instant alerts when a customer places a new order. Keep your team updated and send automated confirmation notifications to build trust with your customers.', 'pushengage' ),
			),
			'cancelled_order' => array(
				'title'       => __( 'Cancelled Order', 'pushengage' ),
				'description' => __( 'Get real-time alerts when a customer cancels an order. Send an automated push notification to re-engage them and recover potential lost sales with exclusive offers or assistance.', 'pushengage' ),
			),
			'failed_order' => array(
				'title'       => __( 'Failed Order', 'pushengage' ),
				'description' => __( 'Get notified immediately when a payment fails, so you can troubleshoot issues quickly. Automatically guide customers to retry their purchase and prevent losing a sale.', 'pushengage' ),
			),
			'order_on_hold' => array(
				'title'       => __( 'Order on Hold', 'pushengage' ),
				'description' => __( 'Inform your customers when an order is placed on hold. Keep your operations smooth by addressing issues promptly and keeping everyone updated.', 'pushengage' ),
			),
			'processing_order' => array(
				'title'       => __( 'Processing Order', 'pushengage' ),
				'description' => __( 'Notify customers as their order moves to the next stage. Keep them engaged and informed, ensuring a positive shopping experience while streamlining internal operations.', 'pushengage' ),
			),
			'completed_order' => array(
				'title'       => __( 'Completed Order', 'pushengage' ),
				'description' => __( 'Celebrate a successful sale by notifying your customers when their order is completed. Encourage repeat purchases with personalized post-sale offers.', 'pushengage' ),
			),
			'refunded_order' => array(
				'title'       => __( 'Refunded Order', 'pushengage' ),
				'description' => __( 'Send instant notifications to inform customers that their refund has been processed. Build trust and improve satisfaction by keeping them in the loop.', 'pushengage' ),
			),
			'order_details' => array(
				'title'       => __( 'Order Details', 'pushengage' ),
				'description' => __( 'Provide updates whenever order details change. Keep customers informed to reduce confusion and support requests, ensuring a seamless experience.', 'pushengage' ),
			),
			'customer_note' => array(
				'title'       => __( 'Customer Note', 'pushengage' ),
				'description' => __( 'Alert customers whenever a note is added to their order. Improve communication and transparency by sending updates of any changes or instructions using personalized notifications.', 'pushengage' ),
			),
			'review_request' => array(
				'title'       => __( 'Review Request', 'pushengage' ),
				'description' => __( 'Send a push notification to request a review.', 'pushengage' ),
			),
			'retry_purchase' => array(
				'title'       => __( 'Retry Purchase Request', 'pushengage' ),
				'description' => __( 'Send a push notification to request a retry purchase with additional offers for failed orders.', 'pushengage' ),
			),
		);

		return apply_filters( 'pushengage_push_notification_events', $notifications );
	}

	/**
	 * Add WooCommerce settings for Push Notifications.
	 *
	 * @since 4.1.0
	 */
	public static function push_notifications_settings() {
		// Redirect to new PushEngage WooCommerce Automation page.
		wp_redirect( admin_url( 'admin.php?page=pushengage#/woocommerce/automation' ) );
		exit;
	}
}

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
		add_action( 'woocommerce_update_options_pe_notifications', array( __CLASS__, 'save_push_notifications_settings' ) );
		add_action( 'woocommerce_update_options_pe_notifications', array( __CLASS__, 'save_notifications_row_settings' ) );

		// Add admin scripts for WooCommerce settings.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ) );

		// Add modal for product importer.
		add_action( 'admin_footer', array( __CLASS__, 'product_importer_modal' ) );

		// Add validation logic for settings.
		add_filter( 'woocommerce_admin_settings_sanitize_option', array( __CLASS__, 'validate_pe_notification_settings' ), 10, 3 );

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
		$notifications   = self::get_push_notification_events();
		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : '';

		if ( $current_section && array_key_exists( $current_section, $notifications ) ) {
			?>
				<h2 class="pe-notification-settings-title">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=pe_notifications' ) ); ?>" style="text-decoration:none;" class="pe-back-link" title="<?php esc_html_e( 'Back to Templates', 'pushengage' ); ?>">
						<span class="dashicons dashicons-arrow-left-alt2"></span>
					</a>
				<?php
					// translators: %s: Notification order type.
					echo sprintf( esc_html__( 'Push Notifications for %s', 'pushengage' ), esc_html( $notifications[ $current_section ]['title'] ) );
				?>
				</h2>
				<?php echo wp_kses_post( wpautop( $notifications[ $current_section ]['description'] ) ); ?>
				<table class="form-table">
					<?php
						woocommerce_admin_fields( self::get_push_notification_section_settings( $current_section ) );
					?>
				</table>
			<?php
		} else {
			echo '<h2>' . esc_html__( 'Push Notifications', 'pushengage' ) . '</h2>';
			echo '<p>' . esc_html__( 'Configure push notifications for various events in your WooCommerce store.', 'pushengage' ) . '</p>';
			?>
			<tr valign="top">
			<td class="wc_emails_wrapper" colspan="2">
				<table id="pe-notification-templates" class="wc_emails widefat" cellspacing="0">
					<thead>
						<tr>
							<?php
								$columns = apply_filters(
									'pushengage_push_notification_columns',
									array(
										'status'       => __( 'Enabled', 'pushengage' ),
										'notification' => __( 'Notification Type', 'pushengage' ),
										'recipients'   => __( 'Recipients', 'pushengage' ),
										'description'  => __( 'Description', 'pushengage' ),
										'manage'       => '',
									)
								);
							foreach ( $columns as $key => $column ) {
								echo '<th class="wc-email-settings-table-' . esc_attr( $key ) . '">' . esc_html( $column ) . '</th>';
							}
							?>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $notifications as $notification_id => $notification ) {
							$notifications_row_settings = get_option( 'pe_notifications_row_setting', array() );
							echo '<tr>';
							foreach ( $columns as $key => $column ) {
								echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">';
								switch ( $key ) {
									case 'status':
										$enable_row = NotificationTemplates::$templates[ $notification_id ]['enable_row'];
										$checked    = isset( $notifications_row_settings[ 'enable_' . $notification_id ] ) ? $notifications_row_settings[ 'enable_' . $notification_id ] : $enable_row;

										?>
											<div class="pe-woocommerce-switch">
												<label class="switch">
													<input 
														type="checkbox" 
														value="yes" 
														<?php checked( 'yes', $checked ); ?>
														name = "pe_notifications_row_setting[enable_<?php echo esc_attr( $notification_id ); ?>]"
														id="pe_notifications_row_setting[enable_<?php echo esc_attr( $notification_id ); ?>]"
														class="woocommerce-switch-checkbox"
													>
													<span class="slider round"></span>
												</label>
											</div>
										<?php
										break;
									case 'notification':
											echo '<a style="font-size:14px;font-weight:700;margin-right:8px;" href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=pe_notifications&section=' . $notification_id ) ) . '">' . esc_html( $notification['title'] ) . '</a>';
											echo wc_help_tip( wp_kses_post( $notification['description'] ) );
										break;
									case 'recipients':
										$notification_settings = get_option( 'pe_notification_' . $notification_id );
										$enable_customer = isset( $notification_settings['enable_customer'] ) ? $notification_settings['enable_customer'] : NotificationTemplates::$templates[ $notification_id ]['enable_customer'];
										$enable_admin = isset( $notification_settings['enable_admin'] ) ? $notification_settings['enable_admin'] : NotificationTemplates::$templates[ $notification_id ]['enable_admin'];
										// display checkbox for admin and customer notifications.
										woocommerce_wp_checkbox(
											array(
												'id'          => 'pe_notification_' . $notification_id . '[enable_admin]',
												'value'       => $enable_admin,
												'label'       => __( 'Admin', 'pushengage' ),
												'description' => '',
												'class'     => 'pe-recipients-checkbox',
											)
										);
										woocommerce_wp_checkbox(
											array(
												'id'          => 'pe_notification_' . $notification_id . '[enable_customer]',
												'value'       => $enable_customer,
												'label'       => __( 'Customer', 'pushengage' ),
												'description' => '',
												'class'     => 'pe-recipients-checkbox',
											)
										);
										break;
									case 'description':
										echo esc_html( $notification['description'] );
										break;
									case 'manage':
										echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=pe_notifications&section=' . $notification_id ) ) . '">' . esc_html__( 'Manage', 'pushengage' ) . '</a>';
										break;
								}
									echo '</td>';
							}
								echo '</tr>';
						}
						?>
					</tbody>
				</table>
			</td>
			<?php
		}
	}

	/**
	 * Get settings for a specific push notification event.
	 *
	 * @param string $section Section name.
	 * @since 4.1.0
	 * @return array $settings Array of settings for the section.
	 */
	public static function get_push_notification_section_settings( $section ) {
		$section_settings = array(
			'customer_section_start' => array(
				'type'  => 'title',
				'title' => __( 'Customer Notification Settings', 'pushengage' ),
				'id'    => 'pushengage_push_notification_customer_section_start_' . $section,
			),
			'enable_' . $section => array(
				'name' => sprintf( __( 'Enable Customer Notification', 'pushengage' ), ucfirst( str_replace( '_', ' ', $section ) ) ),
				'type' => 'checkbox',
				// translators: %s: Notification order type.
				'desc'     => sprintf( __( 'Send push notification to the customer when the %s action is completed.', 'pushengage' ), ucfirst( str_replace( '_', ' ', $section ) ) ),
				'id'       => 'pe_notification_' . $section . '[enable_customer]',
				'default'  => NotificationTemplates::$templates[ $section ]['enable_customer'],
			),
			'notification_title_' . $section => array(
				'name'              => __( 'Notification Title', 'pushengage' ),
				'type'              => 'text',
				'desc'              => __(
					'Available Placeholders: {{customer_name}}, {{order_id}}, {{order_total}}, {{order_date}}, {{order_billing_name}}, {{order_billing_full_name}}, {{checkout_url}}, {{site_url}}, {{shop_url}}, {{dashboard_url}}, {{order_url}}, {{order_admin_url}}',
					'pushengage'
				),
				'id'                => 'pe_notification_' . $section . '[notification_title]',
				'desc_tip'          => true,
				'default'           => NotificationTemplates::$templates[ $section ]['notification_title'],
				'custom_attributes' => array(
					'data-depends-on' => 'pe_notification_' . $section . '[enable_customer]',
					'maxlength'       => 170,
				),
			),
			'notification_message_' . $section => array(
				'name'              => __( 'Notification Message', 'pushengage' ),
				'type'              => 'textarea',
				'css'               => 'width:400px; height: 75px;',
				'desc'              => __(
					'Available Placeholders: {{customer_name}}, {{order_id}}, {{order_total}}, {{order_date}}, {{order_billing_name}}, {{order_billing_full_name}}, {{checkout_url}}, {{site_url}}, {{shop_url}}, {{dashboard_url}}, {{order_url}}, {{order_admin_url}}',
					'pushengage'
				),
				'id'                => 'pe_notification_' . $section . '[notification_message]',
				'desc_tip'          => true,
				'default'           => NotificationTemplates::$templates[ $section ]['notification_message'],
				'custom_attributes' => array(
					'data-depends-on' => 'pe_notification_' . $section . '[enable_customer]',
					'maxlength'       => 256,
				),
			),
			'notification_url_' . $section => array(
				'name'              => __( 'Notification URL', 'pushengage' ),
				'type'              => 'text',
				'desc'              => __(
					'Available Placeholders: {{checkout_url}}, {{site_url}}, {{shop_url}}, {{dashboard_url}}, {{order_url}}, {{order_admin_url}}',
					'pushengage'
				),
				'id'                => 'pe_notification_' . $section . '[notification_url]',
				'desc_tip'          => true,
				'default'           => NotificationTemplates::$templates[ $section ]['notification_url'],
				'custom_attributes' => array(
					'data-depends-on' => 'pe_notification_' . $section . '[enable_customer]',
					'maxlength'       => 1600,
				),
			),
			'customer_section_end' => array(
				'type' => 'sectionend',
				'id'   => 'pushengage_push_notification_section_end_' . $section,
			),
			'admin_section_start' => array(
				'type'  => 'title',
				'title' => __( 'Admin Notification Settings', 'pushengage' ),
				'id'    => 'pushengage_push_notification_admin_section_start_' . $section,
			),
			'enable_admin_notification_' . $section => array(
				// translators: %s: Notification order type.
				'name' => sprintf( __( 'Enable Admin Notification', 'pushengage' ), ucfirst( str_replace( '_', ' ', $section ) ) ),
				'type' => 'checkbox',
				// translators: %s: Notification order type.
				'desc'     => sprintf( __( 'Send push notification to admin(s) when the %s action is completed.', 'pushengage' ), ucfirst( str_replace( '_', ' ', $section ) ) ),
				'id'       => 'pe_notification_' . $section . '[enable_admin]',
				'default'  => NotificationTemplates::$templates[ $section ]['enable_admin'],
			),
			'notification_admin_roles_' . $section => array(
				'name'              => __( 'Admin Roles', 'pushengage' ),
				'type'              => 'multiselect',
				'desc'              => __( 'Select the user roles to send the admin push notifications.', 'pushengage' ),
				'id'                => 'pe_notification_' . $section . '[admin_roles]',
				'class'             => 'wc-enhanced-select',
				'options'           => self::get_admin_roles(),
				'default'           => array( 'administrator' ),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-depends-on' => 'pe_notification_' . $section . '[enable_admin]',
				),
			),
			'admin_notification_title_' . $section => array(
				'name'              => __( 'Admin Notification Title', 'pushengage' ),
				'type'              => 'text',
				'desc'              => __(
					'Available Placeholders: {{customer_name}}, {{order_id}}, {{order_total}}, {{order_date}}, {{order_billing_name}}, {{order_billing_full_name}}, {{checkout_url}}, {{site_url}}, {{shop_url}}, {{dashboard_url}}, {{order_url}}, {{order_admin_url}}',
					'pushengage'
				),
				'id'                => 'pe_notification_' . $section . '[admin_notification_title]',
				'desc_tip'          => true,
				'default'           => NotificationTemplates::$templates[ $section ]['admin_notification_title'],
				'custom_attributes' => array(
					'data-depends-on' => 'pe_notification_' . $section . '[enable_admin]',
					'maxlength'       => 170,
				),
			),
			'admin_notification_message_' . $section => array(
				'name'              => __( 'Admin Notification Message', 'pushengage' ),
				'type'              => 'textarea',
				'css'               => 'width:400px; height: 75px;',
				'desc'              => __(
					'Available Placeholders: {{customer_name}}, {{order_id}}, {{order_total}}, {{order_date}}, {{order_billing_name}}, {{order_billing_full_name}}, {{checkout_url}}, {{site_url}}, {{shop_url}}, {{dashboard_url}}, {{order_url}}, {{order_admin_url}}',
					'pushengage'
				),
				'id'                => 'pe_notification_' . $section . '[admin_notification_message]',
				'desc_tip'          => true,
				'default'           => NotificationTemplates::$templates[ $section ]['admin_notification_message'],
				'custom_attributes' => array(
					'data-depends-on' => 'pe_notification_' . $section . '[enable_admin]',
					'maxlength'       => 256,
				),
			),
			'admin_notification_url_' . $section => array(
				'name'              => __( 'Admin Notification URL', 'pushengage' ),
				'type'              => 'text',
				'desc'              => __(
					'Available Placeholders: {{checkout_url}}, {{site_url}}, {{shop_url}}, {{dashboard_url}}, {{order_url}}, {{order_admin_url}}',
					'pushengage'
				),
				'id'                => 'pe_notification_' . $section . '[admin_notification_url]',
				'desc_tip'          => true,
				'default'           => NotificationTemplates::$templates[ $section ]['admin_notification_url'],
				'custom_attributes' => array(
					'data-depends-on' => 'pe_notification_' . $section . '[enable_admin]',
					'maxlength'       => 1600,
				),
			),
			'section_end' => array(
				'type' => 'sectionend',
				'id'   => 'pushengage_push_notification_section_end_' . $section,
			),
		);

		return apply_filters( 'pushengage_push_notification_section_settings', $section_settings, $section );
	}

	/**
	 * Validate the notification settings.
	 *
	 * @since 4.1.0
	 */
	public static function validate_pe_notification_settings( $value, $option, $raw_value ) {
		// return value if not in the notification settings.
		if ( strpos( $option['id'], 'pe_notification_' ) === false ) {
			return $value;
		}

		// get key only from 'pe_notification_new_order[notification_title]' pattern values.
		$notification_option_key = strstr( $option['id'], '[', true );
		$notification_settings   = get_option( $notification_option_key );

		// validate length of notification title.
		if ( strpos( $option['id'], 'notification_title' ) !== false ) {
			if ( mb_strlen( $raw_value ) > 170 ) {
				// translators: %s: Notification field label.
				WC_Admin_Settings::add_error( sprintf( __( 'Error: %s cannot exceed the length of 50 characters.', 'pushengage' ), $option['name'] ) );
				return ! empty( $notification_settings['notification_title'] ) ? $notification_settings['notification_title'] : null;
			}
		}

		// validate length of notification message.
		if ( strpos( $option['id'], 'notification_message' ) !== false ) {
			if ( mb_strlen( $raw_value ) > 256 ) {
				// translators: %s: Notification field label.
				WC_Admin_Settings::add_error( sprintf( __( 'Error: %s cannot exceed the length of 135 characters.', 'pushengage' ), $option['name'] ) );
				return ! empty( $notification_settings['notification_message'] ) ? $notification_settings['notification_message'] : null;
			}
		}

		// validate length of notification URL.
		if ( strpos( $option['id'], 'notification_url' ) !== false ) {
			if ( mb_strlen( $raw_value ) > 1600 ) {
				// translators: %s: Notification field label.
				WC_Admin_Settings::add_error( sprintf( __( 'Error: %s cannot exceed the length of 1500 characters.', 'pushengage' ), $option['name'] ) );
				return ! empty( $notification_settings['notification_url'] ) ? $notification_settings['notification_url'] : null;
			}
		}

		return $value;
	}

	/**
	 * Save WooCommerce settings for Push Notifications.
	 *
	 * @since 4.1.0
	 */
	public static function save_push_notifications_settings() {
		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : '';

		if ( $current_section && array_key_exists( $current_section, self::get_push_notification_events() ) ) {
			$settings = self::get_push_notification_section_settings( $current_section );

			woocommerce_update_options( $settings );
		}
	}

	/**
	 * Save WooCommerce settings for Push Notifications.
	 *
	 * This method saves the settings for each push notification event row in the WooCommerce settings.
	 * It updates the enabled status for each notification type and the settings for customer and admin notifications.
	 * If a section is specified in the request, it skips saving the row settings.
	 *
	 */
	public static function save_notifications_row_settings() {
		if ( isset( $_GET['section'] ) ) {
			return;
		}

		$notifications                = self::get_push_notification_events();
		$pe_notifications_row_setting = get_option( 'pe_notifications_row_setting', array() );

		foreach ( $notifications as $notification_id => $notification ) {

			// Update notification row settings.
			$pe_notifications_row_setting[ 'enable_' . $notification_id ] = isset( $_POST['pe_notifications_row_setting'][ 'enable_' . $notification_id ] ) ? 'yes' : 'no';

			update_option( 'pe_notifications_row_setting', $pe_notifications_row_setting );

			// Update notification admin and customer settings.
			$notification_settings = get_option( 'pe_notification_' . $notification_id, array() );
			$fields                = array( 'enable_customer', 'enable_admin' );

			foreach ( $fields as $field ) {
				$notification_settings[ $field ] = isset( $_POST[ 'pe_notification_' . $notification_id ][ $field ] ) ? 'yes' : 'no';
			}

			update_option( 'pe_notification_' . $notification_id, $notification_settings );
		}
	}

}

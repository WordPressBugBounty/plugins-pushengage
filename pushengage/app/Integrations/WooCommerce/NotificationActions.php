<?php
namespace Pushengage\Integrations\WooCommerce;

use Pushengage\Integrations\WooCommerce\NotificationHandler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NotificationActions {

	/**
	 * Order Status to notification action mapped array.
	 *
	 * @since 4.1.0
	 * @var array
	 */
	public static $notification_actions = array(
		'completed'  => 'completed_order',
		'cancelled'  => 'cancelled_order',
		'failed'     => 'failed_order',
		'on-hold'    => 'order_on_hold',
		'processing' => 'processing_order',
		'refunded'   => 'refunded_order',
		'pending'    => 'order_details',
	);

	/**
	 * Init Hooks.
	 *
	 * @since 4.1.0
	 * @return void
	 */
	public static function init_hooks() {
		// Legacy - for CPT based orders.
		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'add_pushengage_additional_column' ) );
		// For HPOS based orders.
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( __CLASS__, 'add_pushengage_additional_column' ) );

		// Legacy – for CPT-based orders
		add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'populate_pushengage_column' ), 25, 2 );
		// For HPOS-based orders
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( __CLASS__, 'populate_pushengage_column' ), 25, 2 );

		// Add bulk actions to orders actions dropdown.
		add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( __CLASS__, 'add_pushengage_orders_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( __CLASS__, 'handle_pushengage_order_bulk_actions' ), 10, 3 );
		add_action( 'admin_notices', array( __CLASS__, 'display_pushengage_order_bulk_action_notices' ) );

		add_action( 'admin_init', array( __CLASS__, 'init_admin_hooks' ) );

		// add order details page actions.
		add_filter( 'woocommerce_order_actions', array( __CLASS__, 'add_order_actions' ), 10, 2 );

		add_action( 'woocommerce_order_action_pe_send_status_update', array( __CLASS__, 'handle_order_action_pe_send_status_update' ) );
		add_action( 'woocommerce_order_action_pe_send_review_request', array( __CLASS__, 'handle_order_action_pe_send_review_request' ) );
		add_action( 'woocommerce_order_action_pe_send_retry_purchase', array( __CLASS__, 'handle_order_action_pe_send_retry_purchase' ) );

		// AJAX Actions.
		self::ajax_actions();
	}

	/**
	 * Init Admin Hooks.
	 *
	 * @since 4.1.0
	 * @return void
	 */
	public static function init_admin_hooks() {
		add_filter( 'pushengage_post_settings_metabox_priority', array( __CLASS__, 'set_pushengage_metabox_priority' ) );
		add_filter( 'pushengage_site_not_connected_metabox_priority', array( __CLASS__, 'set_pushengage_metabox_priority' ) );
	}

	/**
	 * Set PushEngage Metabox Priority.
	 *
	 * @since 4.1.0
	 * @return string
	 */
	public static function set_pushengage_metabox_priority( $priority ) {
		$screen = get_current_screen();

		if ( 'product' === $screen->id ) {
			$priority = 'core';
		}

		return $priority;
	}

	/**
	 * AJAX Actions.
	 *
	 * @since 4.1.0
	 * @return void
	 */
	public static function ajax_actions() {
		add_action( 'wp_ajax_pe_woocommerce_order_action', array( __CLASS__, 'handle_pe_woocommerce_order_action' ) );
		add_action( 'wp_ajax_pe_woo_send_product_import_notification', array( __CLASS__, 'send_woo_product_import_notification' ) );
	}

	/**
	 * Add PushEngage Column.
	 *
	 * @since 4.1.0
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function add_pushengage_additional_column( $columns ) {
		$columns['pushengage'] = __( 'Push Notifications', 'pushengage' );
		return $columns;
	}

		/**
	 * Populate PushEngage Column.
	 *
	 * @since 4.1.0
	 * @param string $column Column.
	 * @param object|int $order Order Object or Order ID.
	 * @return void
	 */
	public static function populate_pushengage_column( $column, $order ) {
		if ( 'pushengage' !== $column ) {
			return;
		}

		if ( ! $order ) {
			return;
		}

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		$customer_id  = $order->get_customer_id();
		$order_id     = $order->get_id();
		$order_status = $order->get_status();

		$is_site_connected = pushengage()->is_site_connected();

		// Store common icon HTML for reuse
		$greyed_icons = '<span class="dashicons dashicons-star-filled pe-greyed-icon"></span>';
		$greyed_icons .= '<span class="dashicons dashicons-bell pe-greyed-icon"></span>';
		if ( 'failed' === $order_status ) {
			$greyed_icons .= '<span class="dashicons dashicons-image-rotate pe-greyed-icon"></span>';
		}

		if ( ! $is_site_connected ) {
			echo '<a href="' . esc_url( 'admin.php?page=pushengage#/onboarding' ) . '"><span hover-tooltip="' . esc_html__( 'Connect your site now to use the order push notification features.', 'pushengage' ) . '" tooltip-position="top" class="pe-order-actions">';
			echo $greyed_icons;
			echo '</span></a>';
			return;
		}

		if ( ! $customer_id ) {
			echo '<span hover-tooltip="' . esc_html__( 'Cannot send push notifications to Guest Users', 'pushengage' ) . '" tooltip-position="top" class="pe-order-actions">';
			echo $greyed_icons;
			echo '</span>';
			return;
		}

		$pushengage_subscriber_id = get_user_meta( $customer_id, 'pushengage_subscriber_ids', true );

		if ( ! $pushengage_subscriber_id ) {
			echo '<span hover-tooltip="' . esc_html__( 'Customer is not subscribed for push notifications', 'pushengage' ) . '" tooltip-position="top" class="pe-order-actions">';
			echo $greyed_icons;
			echo '</span>';
			return;
		}

		echo '<div class="pe-order-actions">';
		echo '<a class="pe-order-action" data-notification-action="review_request" hover-tooltip="' . esc_html__( 'Send Review Request', 'pushengage' ) . '" tooltip-position="top" data-order-status="' . esc_attr( $order_status ) . '" data-order-id="' . esc_attr( $order_id ) . '" href="#"><span class="dashicons dashicons-star-filled"></span></a>';
		echo '<a class="pe-order-action" data-notification-action="status_update" data-order-status="' . esc_attr( $order_status ) . '" data-order-id="' . esc_attr( $order_id ) . '" hover-tooltip="' . esc_html__( 'Send Order Status Update', 'pushengage' ) . '" tooltip-position="top" href="#"><span class="dashicons dashicons-bell"></span></a>';

		if ( 'failed' === $order_status ) {
			echo '<a class="pe-order-action" data-notification-action="retry_purchase" data-order-status="' . esc_attr( $order_status ) . '" data-order-id="' . esc_attr( $order_id ) . '" href="#" hover-tooltip="' . esc_html__( 'Send Retry Purchase Request', 'pushengage' ) . '" tooltip-position="top"><span class="dashicons dashicons-image-rotate"></span></a>';
		}
		echo '<a class="pe-order-edit-template" href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=pe_notifications' ) ) . '" hover-tooltip="' . esc_html__( 'Edit Notification Template', 'pushengage' ) . '" tooltip-position="top"><span class="dashicons dashicons-edit"></span></a>';
		echo '</div>';

	}

	/**
	 * Handle PE WooCommerce Order Action.
	 *
	 * @since 4.1.0
	 * @return void
	 */
	public static function handle_pe_woocommerce_order_action() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied. Please make sure you have required permission to perform this action.', 'pushengage' ), 403 );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'pushengage_wc_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Request.', 'pushengage' ) ) );
		}

		if ( ! isset( $_POST['order_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Order ID is missing from the request.', 'pushengage' ) ) );
		}

		if ( ! isset( $_POST['order_status'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Order Status is missing from the request.', 'pushengage' ) ) );
		}

		if ( ! isset( $_POST['notification_action'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Notification Action is missing from the request.', 'pushengage' ) ) );
		}

		$order_id            = absint( $_POST['order_id'] );
		$notification_action = sanitize_text_field( wp_unslash( $_POST['notification_action'] ) );
		$order_status        = sanitize_text_field( wp_unslash( $_POST['order_status'] ) );

		if ( ! $order_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Order ID', 'pushengage' ) ) );
		}

		if ( 'status_update' === $notification_action ) {
			NotificationHandler::send_order_push_notification( $order_id, self::$notification_actions[ $order_status ] );
			wp_send_json_success( array( 'message' => __( 'Status Update Notification sent successfully.', 'pushengage' ) ) );
		}

		if ( 'review_request' === $notification_action ) {
			NotificationHandler::send_order_push_notification(
				$order_id,
				'review_request',
				array(
					'{{review_url}}' => get_permalink( wc_get_page_id( 'shop' ) ),
				)
			);
			wp_send_json_success( array( 'message' => __( 'Review Request Notification sent successfully.', 'pushengage' ) ) );
		}

		if ( 'retry_purchase' === $notification_action ) {
			NotificationHandler::send_order_push_notification( $order_id, 'retry_purchase' );
			wp_send_json_success( array( 'message' => __( 'Retry Purchase Notification sent successfully.', 'pushengage' ) ) );
		}
	}

	/**
	 * Send WooCommerce Product Import Notification.
	 *
	 * @since 4.1.0
	 * @return void
	 */
	public static function send_woo_product_import_notification() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied. Please make sure you have required permission to perform this action.', 'pushengage' ), 403 );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'pushengage_wc_importer_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Request. Nonce verification failed.', 'pushengage' ) ) );
		}

		if ( ! isset( $_POST['data'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Data is missing from the request.', 'pushengage' ) ) );
		}

		$notification_data = NotificationTemplates::format_notification_data( $_POST['data'] );

		$send_notification = pushengage()->send_notification( $notification_data );

		if ( is_wp_error( $send_notification ) ) {
			wp_send_json_error( array( 'message' => $send_notification->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Product Import Notification sent successfully.', 'pushengage' ) ) );
	}

	/**
	 * Add PushEngage Bulk Actions.
	 *
	 * @since 4.1.0
	 * @param array $actions Bulk Actions.
	 * @return array
	 */
	public static function add_pushengage_orders_bulk_actions( $actions ) {
		$actions['pe_send_status_update'] = __( 'Send order status push notification', 'pushengage' );
		$actions['pe_send_review_request'] = __( 'Send review request push notification', 'pushengage' );
		return $actions;
	}

	/**
	 * Handle PushEngage Order Bulk Actions.
	 *
	 * @since 4.1.0
	 * @param string $redirect_to Redirect URL.
	 * @param string $action Bulk Action.
	 * @param array $order_ids Order IDs.
	 * @return string
	 */
	public static function handle_pushengage_order_bulk_actions( $redirect_to, $action, $order_ids ) {
		if ( 'pe_send_status_update' === $action ) {
			foreach ( $order_ids as $order_id ) {
				$order_status = wc_get_order( $order_id )->get_status();
				NotificationHandler::send_order_push_notification( $order_id, self::$notification_actions[ $order_status ] );
			}
			$redirect_to = add_query_arg( 'pe_status', 'status_update_sent', $redirect_to );
		}

		if ( 'pe_send_review_request' === $action ) {
			foreach ( $order_ids as $order_id ) {
				NotificationHandler::send_order_push_notification(
					$order_id,
					'review_request',
					array(
						'{{review_url}}' => get_permalink( wc_get_page_id( 'shop' ) ),
					)
				);
			}
			$redirect_to = add_query_arg( 'pe_status', 'review_request_sent', $redirect_to );
		}

		return $redirect_to;
	}

	/**
	 * Display PushEngage Order Bulk Action Notices.
	 *
	 * @since 4.1.0
	 * @return void
	 */
	public static function display_pushengage_order_bulk_action_notices() {
		if ( ! empty( $_REQUEST['pe_status'] ) ) {
			$status = sanitize_text_field( wp_unslash( $_REQUEST['pe_status'] ) );

			if ( 'status_update_sent' === $status ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Order Status Update Push Notification sent successfully.', 'pushengage' ); ?></p>
				</div>
				<?php
			}

			if ( 'review_request_sent' === $status ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Review Request Push Notification sent successfully.', 'pushengage' ); ?></p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Add Order Actions.
	 *
	 * @since 4.1.0
	 * @param array $actions Order Actions.
	 * @param object $order Order Object.
	 * @return array
	 */
	public static function add_order_actions( $actions, $order ) {
		$customer_id  = $order->get_customer_id();
		$order_status = $order->get_status();

		if ( $customer_id ) {
			$pushengage_subscriber_id = get_user_meta( $customer_id, 'pushengage_subscriber_ids', true );

			if ( $pushengage_subscriber_id ) {
				$actions['pe_send_status_update']  = __( 'Send Order Status Update Push Notification', 'pushengage' );
				$actions['pe_send_review_request'] = __( 'Send Review Request Push Notification', 'pushengage' );

				if ( 'failed' === $order_status ) {
					$actions['pe_send_retry_purchase'] = __( 'Send Retry Purchase Push Notification', 'pushengage' );
				}
			}
		}

		return $actions;
	}

	/**
	 * Handle Order Action PE Send Status Update.
	 *
	 * @since 4.1.0
	 * @param object $order Order Object.
	 * @return void
	 */
	public static function handle_order_action_pe_send_status_update( $order ) {
		NotificationHandler::send_order_push_notification( $order->get_id(), self::$notification_actions[ $order->get_status() ] );
	}

	/**
	 * Handle Order Action PE Send Review Request.
	 *
	 * @since 4.1.0
	 * @param object $order Order Object.
	 * @return void
	 */
	public static function handle_order_action_pe_send_review_request( $order ) {
		NotificationHandler::send_order_push_notification(
			$order->get_id(),
			'review_request',
			array(
				'{{review_url}}' => get_permalink( wc_get_page_id( 'shop' ) ),
			)
		);
	}

	/**
	 * Handle Order Action PE Send Retry Purchase.
	 *
	 * @since 4.1.0
	 * @param object $order Order Object.
	 * @return void
	 */
	public static function handle_order_action_pe_send_retry_purchase( $order ) {
		NotificationHandler::send_order_push_notification( $order->get_id(), 'retry_purchase' );
	}

}

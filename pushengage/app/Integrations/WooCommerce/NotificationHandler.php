<?php
namespace Pushengage\Integrations\WooCommerce;

use Pushengage\Integrations\WooCommerce\NotificationTemplates;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NotificationHandler {

	/**
 * Initialization.
 *
 * @since 4.1.0
 * @return void
 */
	public static function init() {
		// Get Enable Settings.
		$pe_notifications_row_setting = get_option( 'pe_notifications_row_setting', array() );

		// Define notification settings and actions.
		$notifications = array(
			'enable_new_order'           => array(
				'default'  => NotificationTemplates::$templates['new_order']['enable_row'],
				'action'   => 'woocommerce_new_order',
				'callback' => 'send_order_new_push_notification',
			),
			'enable_cancelled_order'     => array(
				'default'  => NotificationTemplates::$templates['cancelled_order']['enable_row'],
				'action'   => 'woocommerce_order_status_cancelled',
				'callback' => 'send_order_cancelled_push_notification',
			),
			'enable_failed_order'        => array(
				'default'  => NotificationTemplates::$templates['failed_order']['enable_row'],
				'action'   => 'woocommerce_order_status_failed',
				'callback' => 'send_order_failed_push_notification',
			),
			'enable_order_on_hold'       => array(
				'default'  => NotificationTemplates::$templates['order_on_hold']['enable_row'],
				'action'   => 'woocommerce_order_status_on-hold',
				'callback' => 'send_order_on_hold_push_notification',
			),
			'enable_processing_order'    => array(
				'default'  => NotificationTemplates::$templates['processing_order']['enable_row'],
				'action'   => 'woocommerce_order_status_processing',
				'callback' => 'send_order_processing_push_notification',
			),
			'enable_completed_order'     => array(
				'default'  => NotificationTemplates::$templates['completed_order']['enable_row'],
				'action'   => 'woocommerce_order_status_completed',
				'callback' => 'send_order_completed_push_notification',
			),
			'enable_refunded_order'      => array(
				'default'  => NotificationTemplates::$templates['refunded_order']['enable_row'],
				'action'   => 'woocommerce_order_status_refunded',
				'callback' => 'send_order_refunded_push_notification',
			),
			'enable_order_details'       => array(
				'default'  => NotificationTemplates::$templates['order_details']['enable_row'],
				'action'   => 'woocommerce_order_status_pending_to_processing',
				'callback' => 'send_order_details_push_notification',
			),
			'enable_customer_note'       => array(
				'default'  => NotificationTemplates::$templates['customer_note']['enable_row'],
				'action'   => 'woocommerce_new_customer_note',
				'callback' => 'send_customer_note_push_notification',
			),
			'enable_review_request'      => array(
				'default'  => NotificationTemplates::$templates['review_request']['enable_row'],
				'action'   => 'woocommerce_order_status_completed',
				'callback' => 'schedule_review_request_notification',
			),
		);

		// Add actions for each notification setting.
		foreach ( $notifications as $key => $config ) {
			$enabled = ! empty( $pe_notifications_row_setting[ $key ] )
			? $pe_notifications_row_setting[ $key ]
			: $config['default'];

			if ( 'yes' === $enabled ) {
				add_action( $config['action'], array( __CLASS__, $config['callback'] ) );
			}
		}

		// Review Request hook.
		add_action( 'pushengage_send_review_request_notification', array( __CLASS__, 'send_review_request_notification' ) );
	}


	/**
	 * Send notification to users by their subscriber hashes.
	 *
	 * @param array $subscriber_hashes Array of subscriber hashes.
	 * @param array $notification_data Notification Data array.
	 * @since 4.1.0
	 * @return WP_Error|array
	 */
	public static function send_notification_to_users( $subscriber_hashes, $notification_data ) {
		if ( empty( $subscriber_hashes ) || empty( $notification_data ) ) {
			return;
		}

		$notification_data['notification_criteria'] = array(
			'filter' => array(
				'value' => array(
					array(
						array(
							'field' => 'device_token_hash',
							'op'    => 'in',
							'value' => array_unique( $subscriber_hashes ),
						),
					),
				),
			),
		);

		return pushengage()->send_notification( $notification_data );
	}

	/**
	 * Send Order Push Notification.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $action Notification Order action.
	 * @param array  $additional_data Additional data for replacements.
	 * @since 4.1.0
	 * @return void
	 */
	public static function send_order_push_notification( $order_id, $action, $additional_data = array() ) {
		if ( empty( $order_id ) ) {
			return;
		}

		// Get Enable Settings.
		$pe_notifications_row_setting = get_option( 'pe_notifications_row_setting', array() );

		// Get notification row enable setting.
		$notification_row_enable = ! empty( $pe_notifications_row_setting[ 'enable_' . $action ] )
			? $pe_notifications_row_setting[ 'enable_' . $action ]
			: NotificationTemplates::$templates[ $action ]['enable_row'];

		if ( 'yes' !== $notification_row_enable ) {

			// if called from AJAX, return error message.
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] ) && 'pe_woocommerce_order_action' === $_REQUEST['action'] ) {
				wp_send_json_error(
					array(
						// translators: %s: Order Action Type.
						'message' => sprintf( __( 'Notification is disabled for order status - %s.', 'pushengage' ), $action ),
					)
				);
			}

			return;
		}

		// Get order object
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Get replacements array
		$replacements = array(
			'{{customer_name}}'           => $order->get_billing_first_name(),
			'{{order_id}}'                => $order_id,
			'{{order_total}}'             => $order->get_total(),
			'{{order_date}}'              => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
			'{{order_billing_name}}'      => $order->get_billing_first_name(),
			'{{order_billing_full_name}}' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'{{checkout_url}}'            => wc_get_checkout_url(),
			'{{site_url}}'                => get_home_url(),
			'{{shop_url}}'                => get_permalink( wc_get_page_id( 'shop' ) ),
			'{{dashboard_url}}'           => get_permalink( wc_get_page_id( 'myaccount' ) ),
			'{{order_url}}'               => $order->get_view_order_url(),
			'{{order_admin_url}}'         => $order->get_edit_order_url(),
		);

		// Add additional data to replacements
		$replacements = array_merge( $replacements, $additional_data );

		$notification_settings = get_option( "pe_notification_{$action}", array() );
		$enable_customer       = ! empty( $notification_settings['enable_customer'] ) ? $notification_settings['enable_customer'] : NotificationTemplates::$templates[ $action ]['enable_customer'];
		$enable_admin          = ! empty( $notification_settings['enable_admin'] ) ? $notification_settings['enable_admin'] : NotificationTemplates::$templates[ $action ]['enable_admin'];

		if ( 'no' === $enable_customer && 'no' === $enable_admin ) {
			// if called from AJAX, return error message.
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				wp_send_json_error(
					array(
						'message' => sprintf( __( 'Notification for order status ( %s ) is disabled for both admin and customers', 'pushengage' ), $action ),
					)
				);
			}

			return;
		}

		// Send notification to customer
		if ( 'yes' === $enable_customer ) {
			$customer_id     = $order->get_customer_id();
			$customer_hashes = get_user_meta( $customer_id, 'pushengage_subscriber_ids', true );

			if ( ! empty( $customer_hashes ) ) {
				// Get customer-specific template
				$customer_notification_data = NotificationTemplates::get_template( $action, $replacements );

				// Send notification to the customer
				$send_customer_notification = self::send_notification_to_users( (array) $customer_hashes, $customer_notification_data );

				if ( is_wp_error( $send_customer_notification ) ) {
					// if called from AJAX, return error message.
					if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
						wp_send_json_error(
							array(
								'message' => $send_customer_notification->get_error_message(),
							)
						);
					}
				}

				// Add private order note for notification sent.
				$order->add_order_note(
					sprintf(
						__( 'Push Notification sent to customer for order status - %s.', 'pushengage' ),
						$action
					)
				);
			}
		}

		// Send notification to admins
		if ( 'yes' === $enable_admin ) {
			$admin_user_roles = ! empty( $notification_settings['admin_roles'] ) ? $notification_settings['admin_roles'] : array(
				'administrator',
			);

			if ( ! empty( $admin_user_roles ) ) {
				$admin_user_ids = get_users(
					array(
						'role__in' => $admin_user_roles,
						'fields'   => 'ID',
					)
				);
				$admin_hashes = array();

				foreach ( $admin_user_ids as $admin_user_id ) {
					$user_hashes = get_user_meta( $admin_user_id, 'pushengage_subscriber_ids', true );
					if ( ! empty( $user_hashes ) ) {
						$admin_hashes = array_merge( $admin_hashes, (array) $user_hashes );
					}
				}

				if ( ! empty( $admin_hashes ) ) {
					// Get admin-specific template
					$admin_notification_data = NotificationTemplates::get_template( $action, $replacements, 'admin' );

					// Send notification to the admins
					self::send_notification_to_users( $admin_hashes, $admin_notification_data );

					// Add private order note for notification sent.
					$order->add_order_note(
						sprintf(
							__( 'Push Notification sent to admin for order status - %s.', 'pushengage' ),
							$action
						)
					);
				}
			}
		}
	}


	/**
	 * Send Order Completed Push Notification.
	 *
	 * @param int $order_id Order ID.
	 * @since 4.1.0
	 * @return void
	 */
	public static function send_order_completed_push_notification( $order_id ) {
		self::send_order_push_notification( $order_id, 'completed_order' );
	}

	/**
	 * Send Order Cancelled Push Notification.
	 *
	 * @param int $order_id Order ID.
	 * @since 4.1.0
	 * @return void
	 */
	public static function send_order_cancelled_push_notification( $order_id ) {
		self::send_order_push_notification( $order_id, 'cancelled_order' );
	}

	/**
	 * Send Order New Push Notification.
	 *
	 * @param int $order_id Order ID.
	 * @since 4.1.0
	 * @return void
	 */
	public static function send_order_new_push_notification( $order_id ) {
		self::send_order_push_notification( $order_id, 'new_order' );
	}

	/**
	 * Send Order Failed Push Notification.
	 *
	 * @param int $order_id Order ID.
	 * @since 4.1.0
	 * @return void
	 */
	public static function send_order_failed_push_notification( $order_id ) {
		self::send_order_push_notification( $order_id, 'failed_order' );
	}

	/**
	 * Send Order On Hold Push Notification.
	 *
	 * @param int $order_id Order ID.
	 * @since 4.1.0
	 * @return void
	 */
	public static function send_order_on_hold_push_notification( $order_id ) {
		self::send_order_push_notification( $order_id, 'order_on_hold' );
	}

	/**
	 * Send Order Processing Push Notification.
	 *
	 * @param int $order_id Order ID.
	 * @since 4.1.0
	 * @return void
	 */
	public static function send_order_processing_push_notification( $order_id ) {
		self::send_order_push_notification( $order_id, 'order_processing' );
	}

	/**
	 * Send Order Refunded Push Notification.
	 *
	 * @param int $order_id Order ID.
	 * @since 4.1.0
	 * @return void
	 */
	public static function send_order_refunded_push_notification( $order_id ) {
		self::send_order_push_notification( $order_id, 'refunded_order' );
	}

	/**
	 * Send Order Details Push Notification.
	 *
	 * @param int $order_id Order ID.
	 * @since 4.1.0
	 * @return void
	 */
	public static function send_order_details_push_notification( $order_id ) {
		self::send_order_push_notification( $order_id, 'order_details' );
	}

	/**
	 * Send Customer Note Push Notification.
	 *
	 * @param array $note_details.
	 * @since 4.1.0
	 * @return void
	 */
	public static function send_customer_note_push_notification( $note_details ) {
		if ( empty( $note_details ) ) {
			return;
		}

		$order_id      = ! empty( $note_details['order_id'] ) ? $note_details['order_id'] : 0;
		$customer_note = ! empty( $note_details['customer_note'] ) ? $note_details['customer_note'] : '';

		if ( empty( $order_id ) || empty( $customer_note ) ) {
			return;
		}

		self::send_order_push_notification( $order_id, 'customer_note', array( '{{customer_note}}' => $customer_note ) );
	}

	/**
	 * Send Review Request Notification.
	 *
	 * @param int $order_id Order ID.
	 * @since 4.1.0
	 */
	public static function send_review_request_notification( $order_id ) {

		if ( empty( $order_id ) ) {
			return;
		}

		self::send_order_push_notification(
			$order_id,
			'review_request',
			array(
				'{{review_url}}' => get_permalink( wc_get_page_id( 'shop' ) ),
			)
		);
	}


	/**
	 * Schedule Review Request Notification.
	 *
	 * @param int $order_id Order ID.
	 * @since 4.1.0
	 * @return void
	 */
	public static function schedule_review_request_notification( $order_id ) {
		if ( empty( $order_id ) ) {
			return;
		}

		// Setup cron job to send review request notification.
		$timestamp = time() + DAY_IN_SECONDS * 5; // 5 days after order completion.
		$hook      = 'pushengage_send_review_request_notification';
		$args      = array( $order_id );

		if ( ! wp_next_scheduled( $hook, $args ) ) {
			wp_schedule_single_event( $timestamp, $hook, $args );
		}
	}

}

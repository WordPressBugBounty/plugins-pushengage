<?php
namespace Pushengage\Integrations\WooCommerce;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NotificationTemplates {

	/**
	 * Default templates for the event types.
	 *
	 * @var array
	 * @since 4.1.0
	 */
	public static $templates;

	/**
	 * Init templates.
	 *
	 * @since 4.1.0
	 * @return void
	 */
	public static function init() {
		self::init_templates();
	}

	/**
	 * Init templates.
	 *
	 * @since 4.1.0
	 * @return void
	 */
	public static function init_templates() {
		self::$templates = array(
			'new_order' => array(
				'notification_title'   => __( '🎉 Order Received!', 'pushengage' ),
				'notification_message' => __( 'Hi {{order_billing_full_name || there}}, we’ve received your order (#{{order_id}}) placed on {{order_date}}. It’s being processed now.', 'pushengage' ),
				'notification_url'     => __( '{{order_url}}', 'pushengage' ),
				'admin_notification_title'   => __( '🛒 New Order Placed!', 'pushengage' ),
				'admin_notification_message' => __(
					'A new order (#{{order_id}}) was placed on {{order_date}}. Check your dashboard for details.',
					'pushengage'
				),
				'admin_notification_url'     => __( '{{order_admin_url}}', 'pushengage' ),
				'enable_row'           => 'yes',
				'enable_admin'         => 'yes',
				'enable_customer'      => 'no',
				'tags'                 => array(
					'Woo',
					'New Order',
				),
			),
			'cancelled_order' => array(
				'notification_title'   => __( '⚠️ Order Cancelled', 'pushengage' ),
				'notification_message' => __( 'Hi {{order_billing_full_name || there}}, your order (#{{order_id}}) has been cancelled. If you need help, contact us anytime.', 'pushengage' ),
				'notification_url'     => __( '{{order_url}}', 'pushengage' ),
				'admin_notification_title'   => __( '⚠️ Order Cancelled', 'pushengage' ),
				'admin_notification_message' => __(
					'Order (#{{order_id}}) placed on {{order_date}} has been cancelled. Check your dashboard for details.',
					'pushengage'
				),
				'admin_notification_url'     => __( '{{order_admin_url}}', 'pushengage' ),
				'enable_row'           => 'yes',
				'enable_admin'         => 'yes',
				'enable_customer'      => 'yes',
				'tags'                 => array(
					'Woo',
					'Cancelled Order',
				),
			),
			'failed_order' => array(
				'notification_title'   => __( '🚨 Payment Failed', 'pushengage' ),
				'notification_message' => __( 'Hi {{order_billing_full_name || there}}, we couldn’t process your payment for order (#{{order_id}}). Please try again.', 'pushengage' ),
				'notification_url'     => __( '{{order_url}}', 'pushengage' ),
				'admin_notification_title'   => __( '🚨 Order Failed', 'pushengage' ),
				'admin_notification_message' => __(
					'Order (#{{order_id}}) placed on {{order_date}} couldn’t be processed. Review the payment details in your dashboard.',
					'pushengage'
				),
				'admin_notification_url'     => __( '{{order_admin_url}}', 'pushengage' ),
				'enable_row'           => 'no',
				'enable_admin'         => 'no',
				'enable_customer'      => 'no',
				'tags'                 => array(
					'Woo',
					'Failed Order',
				),
			),
			'order_on_hold' => array(
				'notification_title'   => __( '🕒 Order On Hold', 'pushengage' ),
				'notification_message' => __( 'Hi {{order_billing_full_name || there}}, your order (#{{order_id}}) is currently on hold. We’ll notify you when it’s back on track.', 'pushengage' ),
				'notification_url'     => __( '{{order_url}}', 'pushengage' ),
				'admin_notification_title'   => __( '🕒 Order On Hold', 'pushengage' ),
				'admin_notification_message' => __(
					'Order (#{{order_id}}) placed on {{order_date}} is now on hold. Check your dashboard for more details.',
					'pushengage'
				),
				'admin_notification_url'     => __( '{{order_admin_url}}', 'pushengage' ),
				'enable_row'           => 'no',
				'enable_admin'         => 'yes',
				'enable_customer'      => 'yes',
				'tags'                 => array(
					'Woo',
					'Order on Hold',
				),
			),
			'processing_order' => array(
				'notification_title'   => __( '🔄 Order Processing', 'pushengage' ),
				'notification_message' => __( 'Hi {{order_billing_full_name || there}}, your order (#{{order_id}}) is being processed. We’ll keep you updated!', 'pushengage' ),
				'notification_url'     => __( '{{order_url}}', 'pushengage' ),
				'admin_notification_title'   => __( '🔄 Order Processing', 'pushengage' ),
				'admin_notification_message' => __(
					'Order (#{{order_id}}) placed on {{order_date}} is now being processed. Keep track of updates in your dashboard.',
					'pushengage'
				),
				'admin_notification_url'     => __( '{{order_admin_url}}', 'pushengage' ),
				'enable_row'           => 'no',
				'enable_admin'         => 'yes',
				'enable_customer'      => 'yes',
				'tags'                 => array(
					'Woo',
					'Processing Order',
				),
			),
			'completed_order' => array(
				'notification_title'   => __( '✅ Order Completed', 'pushengage' ),
				'notification_message' => __( 'Hi {{order_billing_full_name || there}}, your order (#{{order_id}}) is complete! Thanks for shopping with us.', 'pushengage' ),
				'notification_url'     => __( '{{shop_url}}', 'pushengage' ),
				'admin_notification_title'   => __( '✅ Order Completed', 'pushengage' ),
				'admin_notification_message' => __(
					'Order (#{{order_id}}) placed on {{order_date}} has been marked as completed. Review the details in your dashboard.',
					'pushengage'
				),
				'admin_notification_url'     => __( '{{order_admin_url}}', 'pushengage' ),
				'enable_row'           => 'yes',
				'enable_admin'         => 'no',
				'enable_customer'      => 'yes',
				'tags'                 => array(
					'Woo',
					'Completed Order',
				),
			),
			'refunded_order' => array(
				'notification_title'   => __( '💸 Refund Processed', 'pushengage' ),
				'notification_message' => __( 'Hi {{order_billing_full_name || there}}, your refund for order (#{{order_id}}) has been processed. Let us know if you need help!', 'pushengage' ),
				'notification_url'     => __( '{{order_url}}', 'pushengage' ),
				'admin_notification_title'   => __( '💸 Refund Processed', 'pushengage' ),
				'admin_notification_message' => __(
					'Refund for order (#{{order_id}}) has been processed. Check your dashboard for details.',
					'pushengage'
				),
				'admin_notification_url'     => __( '{{order_admin_url}}', 'pushengage' ),
				'enable_row'           => 'no',
				'enable_admin'         => 'yes',
				'enable_customer'      => 'yes',
				'tags'                 => array(
					'Woo',
					'Refunded Order',
				),
			),
			'order_details' => array(
				'notification_title'   => __( '📦 Order Update', 'pushengage' ),
				'notification_message' => __( 'Hi {{order_billing_full_name || there}}, here are the latest details for your order (#{{order_id}}). Check your dashboard for more info', 'pushengage' ),
				'notification_url'     => __( '{{order_url}}', 'pushengage' ),
				'admin_notification_title'   => __( '📦 Order Update', 'pushengage' ),
				'admin_notification_message' => __(
					'Order (#{{order_id}}) has been updated. Check your dashboard for the latest details.',
					'pushengage'
				),
				'admin_notification_url'     => __( '{{order_admin_url}}', 'pushengage' ),
				'enable_row'           => 'yes',
				'enable_admin'         => 'no',
				'enable_customer'      => 'yes',
				'tags'                 => array(
					'Woo',
					'Order Details',
				),
			),
			'customer_note' => array(
				'notification_title'   => __( '📝 A Note for You', 'pushengage' ),
				'notification_message' => __( 'Hi {{order_billing_full_name || there}}, there’s a new note about your order (#{{order_id}}): "{{customer_note || Check your dashboard for details.}}"', 'pushengage' ),
				'notification_url'     => __( '{{order_url}}', 'pushengage' ),
				'admin_notification_title'   => __( '📝 New Customer Note', 'pushengage' ),
				'admin_notification_message' => __(
					'A note was added to order (#{{order_id}}). Check it in your dashboard.',
					'pushengage'
				),
				'admin_notification_url'     => __( '{{order_admin_url}}', 'pushengage' ),
				'enable_row'           => 'yes',
				'enable_admin'         => 'no',
				'enable_customer'      => 'yes',
				'tags'                 => array(
					'Woo',
					'Customer Note',
				),
			),
			'review_request' => array(
				'notification_title'   => __( '⭐ Share Your Thoughts!', 'pushengage' ),
				'notification_message' => __( 'Your order (#{{order_id}}) delivered on {{delivery_date}} is ready for a review! Your feedback helps us improve and serve you better. Leave a review and let us know your thoughts.', 'pushengage' ),
				'notification_url'     => __( '{{review_url}}', 'pushengage' ),
				'admin_notification_title'   => __( '⭐ Review Request', 'pushengage' ),
				'admin_notification_message' => __( 'Your order (#{{order_id}}) delivered on {{delivery_date}} is ready for a review! Your feedback helps us improve and serve you better. Leave a review and let us know your thoughts.', 'pushengage' ),
				'admin_notification_url'     => __( '{{review_url}}', 'pushengage' ),
				'enable_row'           => 'yes',
				'enable_admin'         => 'no',
				'enable_customer'      => 'yes',
				'tags'                 => array(
					'Woo',
					'Review Request',
				),
			),
			'retry_purchase' => array(
				'notification_title'   => __( '🚨 Payment Failed', 'pushengage' ),
				'notification_message' => __( 'Hi {{order_billing_full_name || there}}, we couldn’t process your payment for order (#{{order_id}}). Please try again.', 'pushengage' ),
				'notification_url'     => __( '{{shop_url}}', 'pushengage' ),
				'admin_notification_title'   => __( '🚨 Order Failed', 'pushengage' ),
				'admin_notification_message' => __(
					'Order (#{{order_id}}) placed on {{order_date}} couldn’t be processed. We’re trying to recover your order. If the campaign is enabled, an automatic retry request will be sent to the customer.',
					'pushengage'
				),
				'admin_notification_url' => __( '{{order_admin_url}}', 'pushengage' ),
				'enable_row'           => 'yes',
				'enable_admin'         => 'no',
				'enable_customer'      => 'yes',
				'tags'                 => array(
					'Woo',
					'Retry Purchase Request',
				),
			),
		);

	}

	/**
	 * Format notification data.
	 *
	 * @param array $notification_data Notification data.
	 * @since 4.1.0
	 * @return array
	 */
	public static function format_notification_data( $notification_data ) {
		// Define the required keys and their maximum lengths.
		$required_keys = array(
			'notification_title'   => 85,
			'notification_message' => 135,
			'notification_url'     => 1600,
		);

		// Ensure the notification data has the required keys.
		foreach ( $required_keys as $key => $max_length ) {
			if ( ! isset( $notification_data[ $key ] ) ) {
				$notification_data[ $key ] = '';
			}

			// Trim the data to the maximum length.
			if ( mb_strlen( $notification_data[ $key ] ) > $max_length ) {
				$notification_data[ $key ] = substr( $notification_data[ $key ], 0, $max_length );
			}
		}

		return $notification_data;
	}

	/**
	 * Get the template for the event type.
	 *
	 * @param string $event_type Event type.
	 * @param array $replacements Variable Replacements.
	 * @since 4.1.0
	 * @return string
	 */
	public static function get_template( $event_type, $replacements = array(), $recipient = '' ) {

		if ( ! isset( self::$templates[ $event_type ] ) ) {
			return '';
		}

		$notification_settings = get_option( "pe_notification_{$event_type}", array() );

		// Merge templates with user settings.
		$notification_keys = array( 'notification_title', 'notification_message', 'notification_url' );
		$notification_data = array_map(
			function ( $key ) use ( $notification_settings, $event_type, $recipient ) {
				if ( 'admin' === $recipient ) {
					return ! empty( $notification_settings[ 'admin_' . $key ] ) ? $notification_settings[ 'admin_' . $key ] : self::$templates[ $event_type ][ $key ];
				}
				return ! empty( $notification_settings[ $key ] ) ? $notification_settings[ $key ] : self::$templates[ $event_type ][ $key ];
			},
			$notification_keys
		);

		/**
		 * Replace the variables in the notification data.
		 * Variables are defined in the format {{key || fallback_value}}.
		 * If the key is not found in the replacements array, the fallback value will be used.
		 */
		$notification_data = array_map(
			function ( $data ) use ( $replacements ) {
				return preg_replace_callback(
					'/\{\{(.*?)\s*\|\|\s*(.*?)\}\}/',
					function ( $matches ) use ( $replacements ) {
						$key = trim( $matches[1] ); // Extract the key before '||'
						$fallback = trim( $matches[2] ); // Extract the fallback value
						return ! empty( $replacements[ '{{' . $key . '}}' ] )
							? $replacements[ '{{' . $key . '}}' ]
							: $fallback;
					},
					str_replace( array_keys( $replacements ), array_values( $replacements ), $data )
				);
			},
			$notification_data
		);

		$notification_data = array_combine( $notification_keys, $notification_data );

		// Make sure notification data have valid length after replacements.
		$notification_data = self::format_notification_data( $notification_data );

		/**
		 * Adding Tags.
		 * Tags will be saved in the notifications. We can use this to filter the notifications and create analytics.
		 */
		$notification_data['tags'] = self::$templates[ $event_type ]['tags'];

		return $notification_data;

	}
}

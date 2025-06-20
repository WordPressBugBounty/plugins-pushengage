<?php

namespace Pushengage\Integrations\WooCommerce\Whatsapp\CartAbandonment;

use Pushengage\Logger;


/**
 * Tracker Class for tracking cart abandonment.
 *
 * @since 4.1.2
 */
class Tracker {
	private $table_name;
	private $wc;
	private $cart_abandonment_cutoff_duration;
	private $logger;

	/**
	 * The single instance of the class.
	 *
	 * @since 4.1.2
	 * @var Tracker
	 */
	private static $instance = null;


	/**
	 * Constructor for the Tracker class.
	 *
	 * @since 4.1.2
	 */
	private function __construct() {
		global $wpdb, $woocommerce;

		$this->table_name = $wpdb->prefix . 'pushengage_wc_abandoned_carts';
		$this->wc         = $woocommerce;
		$this->set_cart_abandonment_campaign_data();
		$this->logger = Logger::get_instance( 'PushEngage' );

		// Adding this action to track cart update when woocommerce calculates totals.
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'track_cart_update' ) );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'may_be_handle_empty_cart' ), 10, 2 );

		// Add order status check to update the cart as recovered if the order is completed.
		add_action( 'woocommerce_new_order', array( $this, 'maybe_mark_cart_as_recovered' ), 10, 3 );

		// Register cron schedule to add a custom cron schedule.
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedule' ) );

		// Register cron event to schedule the cron action to check abandoned carts.
		add_action( 'init', array( $this, 'schedule_cron_event' ) );

		// Add the cron action to check abandoned carts.
		add_action( 'pushengage_check_abandoned_carts', array( $this, 'check_abandoned_carts' ) );

		// Add Checkout Tracking Ajax Action.
		add_action( 'wp_ajax_pushengage_save_cart_abandonment_data', array( $this, 'save_cart_abandonment_data' ) );
		add_action( 'wp_ajax_nopriv_pushengage_save_cart_abandonment_data', array( $this, 'save_cart_abandonment_data' ) );
	}

	/**
	 * Get the singleton instance of the class.
	 *
	 * @since 4.1.2
	 * @return Tracker
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Prevent cloning of the instance.
	 *
	 * @since 4.1.2
	 */
	private function __clone() {}

	/**
	 * Prevent serializing of the instance.
	 *
	 * @since 4.1.2
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}

	/**
	 * Load settings.
	 *
	 * @since 4.1.2
	 */
	private function set_cart_abandonment_campaign_data() {
		// Default to 15 minutes
		$cart_abandonment_cutoff_time = 15;
		$campaigns = get_option( 'pushengage_wa_automation_campaigns', array() );

		if ( isset( $campaigns['cart_abandoned'] ) &&
			$campaigns['cart_abandoned']['enabled'] &&
			$campaigns['cart_abandoned']['customerConfig']['enabled'] &&
			isset( $campaigns['cart_abandoned']['customerConfig']['cartAbandonedCutoffTime'] ) ) {
			$cart_abandonment_cutoff_time = $campaigns['cart_abandoned']['customerConfig']['cartAbandonedCutoffTime'];
		}

		$this->cart_abandonment_cutoff_duration = $cart_abandonment_cutoff_time;
	}

	/**
	 * Track cart update.
	 *
	 * @since 4.1.2
	 */
	public function track_cart_update() {
		try {
			if ( ! $this->wc || ! $this->wc->cart || ! $this->wc->session ) {
				return;
			}

			$cart_data = $this->get_cart_data();

			if ( empty( $cart_data ) || empty( $cart_data['items'] ) || empty( $cart_data['customer'] ) ) {
				$this->delete_cart_session();
			} else {
				$this->update_or_insert_cart_session( $cart_data );
			}
		} catch ( \Exception $e ) {
			$this->logger->error( 'Failed to track cart update', $e );
		}
	}


	/**
	 * Ajax action to save cart abandonment data.
	 *
	 * @since 4.1.2
	 */
	public function save_cart_abandonment_data() {
		try {
			check_ajax_referer( 'pushengage_save_cart_abandonment_data', 'nonce' );
			$this->track_cart_update();
			wp_send_json_success();
		} catch ( \Exception $e ) {
			$this->logger->error( 'Failed to save cart abandonment data', $e );
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Delete cart session if the cart is empty.
	 *
	 * @since 4.1.2
	 * @param string $cart_item_key The cart item key.
	 * @param \WC_Cart $cart The cart object.
	 */
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
	public function may_be_handle_empty_cart( $cart_item_key, $cart ) {
		try {
			if ( $cart->is_empty() ) {
				$this->delete_cart_session();
			}
		} catch ( \Exception $e ) {
			$this->logger->error( 'Failed to handle empty cart', $e );
		}
	}

	/**
	 * Get cart and customer data from WooCommerce session and cart.
	 *
	 * @since 4.1.2
	 */
	private function get_cart_data() {
		// Check if WooCommerce is active and cart and session is available
		if ( ! class_exists( 'WooCommerce' ) || ! $this->wc || ! $this->wc->cart || ! $this->wc->session ) {
			return null;
		}

		$cart_items = array();
		foreach ( $this->wc->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product = $cart_item['data'];
			$cart_items[] = array(
				'product_id' => $product->get_id(),
				'quantity'   => $cart_item['quantity'],
				'price'      => $product->get_price(),
				'name'       => $product->get_name(),
			);
		}

		$customer_data = array();
		if ( $this->wc->session ) {
			$customer = $this->wc->session->get( 'customer' );
			if ( $customer ) {
				$fields = array(
					'id',
					'first_name',
					'last_name',
					'company',
					'phone',
					'email',
					'address',
					'address_1',
					'address_2',
					'city',
					'state',
					'postcode',
					'country',
					'shipping_first_name',
					'shipping_last_name',
					'shipping_company',
					'shipping_phone',
					'shipping_address',
					'shipping_address_1',
					'shipping_address_2',
					'shipping_city',
					'shipping_state',
					'shipping_postcode',
					'shipping_country',
				);

				foreach ( $fields as $field ) {
					if ( isset( $customer[ $field ] ) && ! empty( $customer[ $field ] ) ) {
						$customer_data[ $field ] = $customer[ $field ];
					}
				}
			}
		}

		return array(
			'items'    => $cart_items,
			'total'    => $this->wc->cart->total,
			'customer' => $customer_data,
		);
	}

	/**
	 * Update cart session.
	 *
	 * @since 4.1.2
	 */
	private function update_or_insert_cart_session( $cart_data ) {
		global $wpdb;
		$cart_token    = $this->wc->session->get( 'pushengage_cart_token' );
		$existing_cart = null;
		$user_id = isset( $cart_data['customer']['id'] ) ? $cart_data['customer']['id'] : null;

		if ( ! empty( $cart_token ) ) {
			// Get the non-recovered existing cart for the cart token.
			$query = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$this->table_name} WHERE cart_token = %s AND recovered_at IS NULL",
				$cart_token
			);
			$existing_cart = $wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// If no cart is found by cart_token then Find the non-recovered
		// existing cart for the user.
		if ( empty( $existing_cart ) && ! empty( $user_id ) ) {
			$query = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$this->table_name} WHERE user_id = %d AND recovered_at IS NULL",
				$user_id
			);
			$existing_cart = $wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// For existing carts, update the cart data.
		if ( $existing_cart ) {
			$updated_cart_data = array(
				'user_id'       => $user_id,
				'cart_contents' => maybe_serialize( $cart_data ),
				'updated_at'    => current_time( 'mysql', true ),
				'cart_total'    => floatval( $cart_data['total'] ),
			);

			// if the cart token is not set in session, set it from the existing cart.
			if ( empty( $cart_token ) ) {
				$this->wc->session->set( 'pushengage_cart_token', $existing_cart->cart_token );
			}

			$wpdb->update(
				$this->table_name,
				$updated_cart_data,
				array( 'id' => $existing_cart->id )
			);
		} else {
			// ensure that either customer phone or shipping_phone is exists to
			// track the cart.
			if ( empty( $cart_data['customer'] ) || ( empty( $cart_data['customer']['phone'] ) && empty( $cart_data['customer']['shipping_phone'] ) ) ) {
				return;
			}

			// generate a new cart token and set it in session.
			$cart_token = wp_generate_password( 20, false );
			$this->wc->session->set( 'pushengage_cart_token', $cart_token );

			$wpdb->insert(
				$this->table_name,
				array(
					'user_id'       => isset( $cart_data['customer']['id'] ) ? $cart_data['customer']['id'] : null,
					'cart_token'    => $cart_token,
					'cart_contents' => maybe_serialize( $cart_data ),
					'cart_total'    => floatval( $cart_data['total'] ),
					'created_at'    => current_time( 'mysql', true ),
					'updated_at'    => current_time( 'mysql', true ),
				)
			);

			// If we are creating a new session for logged in user then delete
			// all other $cart for the user which are is not recovered to
			// prevent multiple active abandoned cart which can lead to
			// multiple notification being sent to the user
			if ( ! empty( $user_id ) ) {
				$wpdb->query(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						"DELETE FROM {$this->table_name} WHERE user_id = %d AND cart_token != %s AND recovered_at IS NULL",
						$user_id,
						$cart_token
					)
				);
			}
		}
	}

	/**
	 * Delete cart session.
	 *
	 * @since 4.1.2
	 */
	private function delete_cart_session() {
		global $wpdb;

		// delete the cart token from session.
		$cart_token = null;
		if ( $this->wc && $this->wc->session ) {
			$cart_token = $this->wc->session->get( 'pushengage_cart_token' );
			$this->wc->session->__unset( 'pushengage_cart_token' );
		}

		// For logged in users, delete all the carts from the database which are not
		// recovered. using user_id as the condition.
		$user_id = get_current_user_id();
		if ( ! empty( $user_id ) ) {
			$wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"DELETE FROM {$this->table_name} WHERE (user_id = %d OR cart_token = %s) AND recovered_at IS NULL",
					$user_id,
					$cart_token
				)
			);
			return;
		}

		// For guest users, delete the carts which are not notified using
		// cart_token as the condition.
		if ( ! empty( $cart_token ) ) {
			$wpdb->delete(
				$this->table_name,
				array(
					'cart_token' => $cart_token,
					'recovered_at' => null,
				)
			);
			return;
		}
	}

	/**
	 * Check abandoned carts.
	 *
	 * @since 4.1.2
	 */
	public function check_abandoned_carts() {
		global $wpdb;
		try {
			// Get max execution time and set our limit to 50%
			$max_execution_time = (int) ini_get( 'max_execution_time' );
			$time_limit         = ( $max_execution_time > 0 ) ? ( $max_execution_time * 0.5 ) : 30; // Default to 30 seconds if max_execution_time is 0
			$start_time         = time();

			// Get the cutoff duration, max notified count limit, re-notify delay and re-notify datetime
			$cutoff_duration          = $this->cart_abandonment_cutoff_duration;
			$cutoff_datetime          = gmdate( 'Y-m-d H:i:s', strtotime( "-{$cutoff_duration} minutes" ) );
			$renotify_delay_minutes   = 12 * 60; // 12 hours
			$renotify_datetime        = gmdate( 'Y-m-d H:i:s', strtotime( "-{$renotify_delay_minutes} minutes" ) );

			// Process carts in batches of 10
			$batch_size = 10;
			$offset     = 0;

			while ( true ) {
				// Check if we're approaching the time limit
				$current_time = time();
				if ( ( $current_time - $start_time ) > $time_limit ) {
					$this->logger->debug( 'Time limit reached, stopping cart check' );
					break;
				}

				// Get batch of abandoned carts
				$query = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT * FROM {$this->table_name}
				WHERE ( updated_at < %s AND recovered_at IS NULL AND notified_at IS NULL )
				OR ( updated_at < %s and updated_at > notified_at AND notified_at < %s AND recovered_at IS NULL AND notified_at IS NOT NULL)
				LIMIT %d OFFSET %d",
					$cutoff_datetime,
					$cutoff_datetime,
					$renotify_datetime,
					$batch_size,
					$offset
				);
				$abandoned_carts = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				// If no more carts to process, break the loop
				if ( empty( $abandoned_carts ) ) {
					break;
				}

				$this->logger->debug( 'Found ' . count( $abandoned_carts ) . ' abandoned carts to process' );

				foreach ( $abandoned_carts as $item ) {
					// Check if we're approaching the time limit
					$current_time = time();
					if ( ( $current_time - $start_time ) > $time_limit ) {
						break;
					}
					$cart = new AbandonedCart( $item );

					// cleanup the cart if phone number is not available
					if ( ! $cart->has_valid_phone_number() ) {
						$this->logger->debug( 'Deleting cart #' . $cart->get_id() . ' because it does not have a valid phone number' );
						$this->delete_cart( $cart->get_id() );
						continue;
					}

					// if cart was abandoned for more that 7 days then delete the cart
					if ( $cart->is_abandoned_for_more_than_7_days() ) {
						$this->logger->debug( 'Deleting cart #' . $cart->get_id() . ' because it is abandoned for more than 7 days' );
						$this->delete_cart( $cart->get_id() );
						continue;
					}

					// if cart was already notified 3 times, then do not send notification
					if ( $cart->get_notified_count() >= 3 ) {
						$this->logger->debug( 'Skipping cart #' . $cart->get_id() . ' because it was already notified 3 times' );
						continue;
					}

					// if cart was already notified in the last 12 hours, then do not send the notification
					if ( $cart->get_notified_at() && strtotime( $cart->get_notified_at() ) > strtotime( '-12 hours' ) ) {
						$this->logger->debug( 'Skipping cart #' . $cart->get_id() . ' because it was already notified in the last 12 hours' );
						continue;
					}

					// send notification to the customer
					$this->send_abandoned_cart_notification( $cart );
				}

				$offset += $batch_size;
				// If the number of abandoned carts is less than the batch size, break the loop
				if ( count( $abandoned_carts ) < $batch_size ) {
					break;
				}
			}
			$this->delete_expired_carts();

		} catch ( \Exception $e ) {
			$this->logger->error( 'Error checking abandoned carts', $e );
		}
	}

	/**
	 * Delete expired carts.
	 *
	 * @since 4.1.2
	 */
	public function delete_expired_carts() {
		global $wpdb;
		// Delete carts which are not recovered and updated more than 7 days ago
		$cutoff_datetime = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM {$this->table_name} WHERE updated_at < %s AND recovered_at IS NULL",
				$cutoff_datetime
			)
		);
	}

	/**
	 * Maybe mark cart as recovered.
	 *
	 * @since 4.1.2
	 */
	public function maybe_mark_cart_as_recovered( $order_id ) {
		global $wpdb;

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$cart_token = null;
		if ( $this->wc && $this->wc->session ) {
			$cart_token = $this->wc->session->get( 'pushengage_cart_token' );
		}

		$user_id = $order->get_user_id();

		// if the user id and cart token are not set, return.
		if ( empty( $user_id ) && empty( $cart_token ) ) {
			return;
		}

		// update the cart content with the order id as we no longer need
		// other details after cart is recovered.
		$cart_content = array( 'order_id' => $order_id );

		$update_count = 0;
		if ( ! empty( $cart_token ) ) {
			$query = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$this->table_name} SET recovered_at = %s, cart_contents = %s WHERE cart_token = %s AND notified_at IS NOT NULL",
				current_time( 'mysql', true ),
				maybe_serialize( $cart_content ),
				$cart_token
			);
			$update_count = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		if ( 0 === $update_count && ! empty( $user_id ) ) {
			$query = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$this->table_name}
				SET recovered_at = %s,
					cart_contents = %s
				WHERE user_id = %d AND notified_at IS NOT NULL and recovered_at IS NULL",
				current_time( 'mysql', true ),
				maybe_serialize( $cart_content ),
				$user_id
			);
			$update_count = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// After checkout, delete all the carts which are not notified or recovered.
		// We will create a new cart for the user, if user starts shopping again.
		if ( ! empty( $user_id ) ) {
			$wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"DELETE FROM {$this->table_name} WHERE user_id = %d AND (notified_at IS NULL OR recovered_at is NULL)",
					$user_id
				)
			);
		}

		if ( $cart_token && $this->wc && $this->wc->session ) {
			$this->wc->session->__unset( 'pushengage_cart_token' );
		}
	}

	/**
	 * Mark cart as notified.
	 *
	 * @since 4.1.2
	 * @param int $cart_id The cart ID to mark as notified.
	 */
	public function mark_cart_as_notified( $cart_id ) {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$this->table_name} SET notified_at = %s, notified_count = notified_count + 1 WHERE id = %d",
				current_time( 'mysql', true ),
				$cart_id
			)
		);
	}

	/**
	 * Send abandoned cart notification.
	 *
	 * @since 4.1.2
	 */
	private function send_abandoned_cart_notification( $cart ) {
		do_action( 'pushengage_send_abandoned_cart_notification', $cart );
	}

	/**
	 * Add custom cron schedule to check abandoned carts every 10 minutes.
	 *
	 * @since 4.1.2
	 */
	public function add_cron_schedule( $schedules ) {
		$schedules['pushengage_check_abandoned_cart_interval'] = array(
			'interval' => 600,
			'display'  => __( 'Every 10 Minutes', 'pushengage' ),
		);
		return $schedules;
	}

	/**
	 * Schedule the cron event if it's not already scheduled to check abandoned
	 * carts every 10 minutes.
	 *
	 * @since 4.1.2
	 */
	public function schedule_cron_event() {
		if ( ! wp_next_scheduled( 'pushengage_check_abandoned_carts' ) ) {
			wp_schedule_event( time(), 'pushengage_check_abandoned_cart_interval', 'pushengage_check_abandoned_carts' );
		}
	}

	/**
	 * Delete cart.
	 *
	 * @since 4.1.2
	 */
	public function delete_cart( $cart_id ) {
		global $wpdb;

		return $wpdb->delete(
			$this->table_name,
			array( 'id' => $cart_id )
		);
	}
}

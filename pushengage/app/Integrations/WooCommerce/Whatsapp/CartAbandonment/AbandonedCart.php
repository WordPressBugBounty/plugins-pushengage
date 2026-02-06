<?php

namespace Pushengage\Integrations\WooCommerce\Whatsapp\CartAbandonment;

use Pushengage\Integrations\WooCommerce\Whatsapp\WhatsappHelper;

/**
 * Abandoned Cart Class.
 *
 * @since 4.1.2
 */
class AbandonedCart {
	/**
	 * The cart record from database.
	 *
	 * @since 4.1.2
	 * @var object
	 */
	private $cart;

	/**
	 * The unserialized cart contents.
	 *
	 * @since 4.1.2
	 * @var array|null
	 */
	private $cart_contents;

	/**
	 * Constructor for the AbandonedCart class.
	 *
	 * @since 4.1.2
	 * @param object $cart The cart record from database.
	 */
	public function __construct( $cart ) {
		$this->cart = $cart;
		$this->cart_contents = maybe_unserialize( $cart->cart_contents );
	}

	/**
	 * Get the cart ID.
	 *
	 * @since 4.1.2
	 * @return int
	 */
	public function get_id() {
		return (int) $this->cart->id;
	}

	/**
	 * Get the user ID.
	 *
	 * @since 4.1.2
	 * @return int|null
	 */
	public function get_user_id() {
		return ! empty( $this->cart->user_id ) ? (int) $this->cart->user_id : 0;
	}

	/**
	 * Get the cart token.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_cart_token() {
		return $this->cart->cart_token;
	}

	/**
	 * Get the cart total.
	 *
	 * @since 4.1.2
	 * @return float
	 */
	public function get_cart_total() {
		return (float) $this->cart->cart_total;
	}

	/**
	 * Get the cart items.
	 *
	 * @since 4.1.2
	 * @return array
	 */
	public function get_cart_items() {
		return isset( $this->cart_contents['items'] ) ? $this->cart_contents['items'] : array();
	}

	/**
	 * Get the customer data.
	 *
	 * @since 4.1.2
	 * @return array
	 */
	public function get_customer_data() {
		return isset( $this->cart_contents['customer'] ) ? $this->cart_contents['customer'] : array();
	}


	/**
	 * Get the customer email.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_customer_email() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['email'] ) ? $customer['email'] : '';
	}

	/**
	 * Get the cart creation date.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_created_at() {
		return $this->cart->created_at;
	}

	/**
	 * Get the cart last update date.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_updated_at() {
		return $this->cart->updated_at;
	}

	/**
	 * Get the cart notification date.
	 *
	 * @since 4.1.2
	 * @return string|null
	 */
	public function get_notified_at() {
		return $this->cart->notified_at;
	}

	/**
	 * Get the cart recovery date.
	 *
	 * @since 4.1.2
	 * @return string|null
	 */
	public function get_recovered_at() {
		return $this->cart->recovered_at;
	}

	/**
	 * Check if the cart has been notified.
	 *
	 * @since 4.1.2
	 * @return bool
	 */
	public function is_notified() {
		return ! empty( $this->cart->notified_at );
	}

	/**
	 * Check if the cart has been recovered.
	 *
	 * @since 4.1.2
	 * @return bool
	 */
	public function is_recovered() {
		return ! empty( $this->cart->recovered_at );
	}

	/**
	 * Get the raw cart record.
	 *
	 * @since 4.1.2
	 * @return object
	 */
	public function get_raw_cart() {
		return $this->cart;
	}

	/**
	 * Get billing first name.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_billing_first_name() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['first_name'] ) ? $customer['first_name'] : '';
	}

	/**
	 * Get billing last name.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_billing_last_name() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['last_name'] ) ? $customer['last_name'] : '';
	}

	public function get_billing_full_name() {
		return $this->get_billing_first_name() . ' ' . $this->get_billing_last_name();
	}

	/**
	 * Get billing company.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_billing_company() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['company'] ) ? $customer['company'] : '';
	}

	/**
	 * Get billing phone.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_billing_phone() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['phone'] ) ? $customer['phone'] : '';
	}


	/**
	 * Get billing address line 1.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_billing_address_1() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['address_1'] ) ? $customer['address_1'] : '';
	}

	/**
	 * Get billing address line 2.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_billing_address_2() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['address_2'] ) ? $customer['address_2'] : '';
	}

	/**
	 * Get billing city.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_billing_city() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['city'] ) ? $customer['city'] : '';
	}

	/**
	 * Get billing state.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_billing_state() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['state'] ) ? $customer['state'] : '';
	}

	/**
	 * Get billing postcode.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_billing_postcode() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['postcode'] ) ? $customer['postcode'] : '';
	}

	/**
	 * Get billing country.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_billing_country() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['country'] ) ? $customer['country'] : '';
	}

	/**
	 * Get billing address.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_billing_address() {
		return array(
			'first_name' => $this->get_billing_first_name(),
			'last_name'  => $this->get_billing_last_name(),
			'company'    => $this->get_billing_company(),
			'address_1'  => $this->get_billing_address_1(),
			'address_2'  => $this->get_billing_address_2(),
			'city'       => $this->get_billing_city(),
			'state'      => $this->get_billing_state(),
			'postcode'   => $this->get_billing_postcode(),
			'country'    => $this->get_billing_country(),
		);
	}

	/**
	 * Get shipping first name.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_shipping_first_name() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['shipping_first_name'] ) ? $customer['shipping_first_name'] : '';
	}

	/**
	 * Get shipping last name.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_shipping_last_name() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['shipping_last_name'] ) ? $customer['shipping_last_name'] : '';
	}

	public function get_shipping_full_name() {
		return $this->get_shipping_first_name() . ' ' . $this->get_shipping_last_name();
	}

	/**
	 * Get shipping company.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_shipping_company() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['shipping_company'] ) ? $customer['shipping_company'] : '';
	}

	/**
	 * Get shipping phone.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_shipping_phone() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['shipping_phone'] ) ? $customer['shipping_phone'] : '';
	}

	/**
	 * Get shipping address.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_shipping_address() {
		return array(
			'first_name' => $this->get_shipping_first_name(),
			'last_name'  => $this->get_shipping_last_name(),
			'company'    => $this->get_shipping_company(),
			'address_1'  => $this->get_shipping_address_1(),
			'address_2'  => $this->get_shipping_address_2(),
			'city'       => $this->get_shipping_city(),
			'state'      => $this->get_shipping_state(),
			'postcode'   => $this->get_shipping_postcode(),
			'country'    => $this->get_shipping_country(),
		);
	}

	/**
	 * Get shipping address line 1.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_shipping_address_1() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['shipping_address_1'] ) ? $customer['shipping_address_1'] : '';
	}

	/**
	 * Get shipping address line 2.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_shipping_address_2() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['shipping_address_2'] ) ? $customer['shipping_address_2'] : '';
	}

	/**
	 * Get shipping city.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_shipping_city() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['shipping_city'] ) ? $customer['shipping_city'] : '';
	}

	/**
	 * Get shipping state.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_shipping_state() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['shipping_state'] ) ? $customer['shipping_state'] : '';
	}

	/**
	 * Get shipping postcode.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_shipping_postcode() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['shipping_postcode'] ) ? $customer['shipping_postcode'] : '';
	}

	/**
	 * Get shipping country.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_shipping_country() {
		$customer = $this->get_customer_data();
		return ! empty( $customer['shipping_country'] ) ? $customer['shipping_country'] : '';
	}

	/**
	 * Check if either billing or shipping phone number is valid.
	 *
	 * @since 4.1.2
	 * @return bool
	 */
	public function has_valid_phone_number() {
		$billing_phone = $this->get_billing_phone();
		$shipping_phone = $this->get_shipping_phone();

		return WhatsappHelper::is_valid_phone_number( $billing_phone ) ||
			WhatsappHelper::is_valid_phone_number( $shipping_phone );
	}

	/**
	 * Get formatted billing phone number in E.164 format.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_formatted_billing_phone() {
		return WhatsappHelper::format_phone_number( $this->get_billing_phone(), $this->get_billing_country() );
	}

	/**
	 * Get formatted shipping phone number in E.164 format.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_formatted_shipping_phone() {
		return WhatsappHelper::format_phone_number( $this->get_shipping_phone(), $this->get_shipping_country() );
	}

	/**
	 * Get formatted billing address.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_formatted_billing_address() {
		return WhatsappHelper::format_full_address( $this->get_billing_address() );
	}

	/**
	 * Get formatted shipping address.
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_formatted_shipping_address() {
		return WhatsappHelper::format_full_address( $this->get_shipping_address() );
	}

	/**
	 * Get cart variables.
	 *
	 * @since 4.1.2
	 * @return array
	 */
	public function get_cart_variables() {
		return array(
			'cart_items_count'    => count( $this->get_cart_items() ),
			'cart_total'          => $this->get_cart_total(),
			'cart_currency'       => get_woocommerce_currency(),

			'billing_first_name'  => $this->get_billing_first_name(),
			'billing_last_name'   => $this->get_billing_last_name(),
			'billing_full_name'   => $this->get_billing_full_name(),
			'billing_company'     => $this->get_billing_company(),
			'billing_address_1'   => $this->get_billing_address_1(),
			'billing_address_2'   => $this->get_billing_address_2(),
			'billing_city'        => $this->get_billing_city(),
			'billing_state'       => $this->get_billing_state(),
			'billing_postcode'    => $this->get_billing_postcode(),
			'billing_country'     => $this->get_billing_country(),
			'billing_phone'       => $this->get_billing_phone(),
			'billing_email'       => $this->get_customer_email(),
			'billing_address'     => $this->get_formatted_billing_address(),

			'shipping_first_name' => $this->get_shipping_first_name(),
			'shipping_last_name'  => $this->get_shipping_last_name(),
			'shipping_full_name'  => $this->get_shipping_full_name(),
			'shipping_address_1'  => $this->get_shipping_address_1(),
			'shipping_address_2'  => $this->get_shipping_address_2(),
			'shipping_city'       => $this->get_shipping_city(),
			'shipping_state'      => $this->get_shipping_state(),
			'shipping_postcode'   => $this->get_shipping_postcode(),
			'shipping_country'    => $this->get_shipping_country(),
			'shipping_phone'      => $this->get_shipping_phone(),
			'shipping_address'    => $this->get_formatted_shipping_address(),
		);
	}

	/**
	 * Get phone numbers.
	 *
	 * @since 4.1.2
	 * @param string $recipient_type The recipient type.
	 * @return array
	 */
	public function get_phone_numbers( $recipient_type = 'billing' ) {
		$phone_numbers = array();

		switch ( $recipient_type ) {
			case 'billing':
				$phone_numbers[] = $this->get_formatted_billing_phone();
				break;
			case 'shipping':
				$phone_numbers[] = $this->get_formatted_shipping_phone();
				break;
			case 'both':
				$phone_numbers[] = $this->get_formatted_billing_phone();
				$phone_numbers[] = $this->get_formatted_shipping_phone();
				break;
		}
		// filter out empty phone numbers
		$phone_numbers = array_filter(
			$phone_numbers,
			function ( $phone_number ) {
				return ! empty( $phone_number );
			}
		);

		// remove duplicates
		$phone_numbers = array_unique( $phone_numbers );
		return $phone_numbers;
	}

	/**
	 * Mark the cart as notified.
	 *
	 * @since 4.1.2
	 */
	public function mark_as_notified() {
		$tracker = Tracker::get_instance();
		$tracker->mark_cart_as_notified( $this->get_id() );
	}

	/**
	 * Check if the cart is abandoned for more than 7 days.
	 *
	 * @since 4.1.2
	 * @return bool
	 */
	public function is_abandoned_for_more_than_7_days() {
		$updated_at = strtotime( $this->cart->updated_at );
		$now = time();
		$diff = $now - $updated_at;
		return $diff > 7 * 24 * 60 * 60;
	}

	/**
	 * Get the notified count.
	 *
	 * @since 4.1.2
	 * @return int
	 */
	public function get_notified_count() {
		return (int) $this->cart->notified_count;
	}
}

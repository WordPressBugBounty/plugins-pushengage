<?php

namespace Pushengage\Integrations\WooCommerce\Whatsapp;

/**
 * WhatsApp Helper Class.
 * Contains common helper methods and constants for the WhatsApp module.
 *
 * @since 4.1.2
 */
class WhatsappHelper {
	/**
	 * Minimum length for a valid phone number.
	 *
	 * @since 4.1.2
	 * @var int
	 */
	const MIN_PHONE_LENGTH = 10;

	/**
	 * Maximum length for a valid phone number.
	 *
	 * @since 4.1.2
	 * @var int
	 */
	const MAX_PHONE_LENGTH = 20;

	/**
	 * Prevent instantiation of the class.
	 *
	 * @since 4.1.2
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of the class.
	 *
	 * @since 4.1.2
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing of the class.
	 *
	 * @since 4.1.2
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize static class' );
	}

	public static function is_valid_whatsapp_credentials( $credentials ) {
		if ( empty( $credentials ) ) {
			return false;
		}
		if ( empty( $credentials['accessToken'] ) || empty( $credentials['phoneNumberId'] ) || empty( $credentials['whatsappBusinessId'] ) ) {
			return false;
		}

		if ( ! isset( $credentials['isDecryptedAccessTokenValid'] ) ) {
			return false;
		}

		return $credentials['isDecryptedAccessTokenValid'];
	}

	/**
	 * Check if the phone number is valid.
	 * A valid phone number should:
	 * 1. Not be empty
	 * 2. Contain only digits, spaces, plus sign, and hyphens
	 * 3. Have a reasonable length (7-15 digits)
	 *
	 * @since 4.1.2
	 * @param string $phone The phone number to validate.
	 * @return bool
	 */
	public static function is_valid_phone_number( $phone ) {
		if ( empty( $phone ) ) {
			return false;
		}

		// Count actual digits
		$digit_count = strlen( preg_replace( '/[^0-9]/', '', $phone ) );

		// Check if the number of digits is within reasonable range
		return $digit_count >= self::MIN_PHONE_LENGTH && $digit_count <= self::MAX_PHONE_LENGTH;
	}

	/**
	 * Format phone number to E.164 format with country calling code.
	 *
	 * @since 4.1.2
	 * @param string $phone The phone number to format.
	 * @param string $country_code ISO country code (optional)
	 * @return string
	 */
	public static function format_phone_number( $phone, $country_code = '' ) {
		if ( ! self::is_valid_phone_number( $phone ) ) {
			return '';
		}

		// Remove all non-digit characters
		$phone_number = preg_replace( '/[^0-9]/', '', $phone );

		if ( strlen( $phone_number ) <= 10 ) {
			$country_calling_code = self::get_country_calling_code( $country_code );
			$phone_number = $country_calling_code . $phone_number;
		}

		// If the phone number is less than 10 digits, return empty string
		if ( strlen( $phone_number ) < 10 ) {
			return '';
		}

		return $phone_number;
	}

	/**
	 * Get country calling code based on country code. If country code is not
	 * provided, it will use the base country from WooCommerce.
	 *
	 * @since 4.1.2
	 *
	 * @param string $country_code ISO country code (optional)
	 * @return string Country calling code without '+' prefix
	 */
	public static function get_country_calling_code( $country_code = '' ) {
		if ( class_exists( 'WC_Countries' ) ) {
			$wc_countries = new \WC_Countries();
			$base_country = $country_code ? $country_code : $wc_countries->get_base_country();
			$calling_code = method_exists( $wc_countries, 'get_country_calling_code' ) ?
				$wc_countries->get_country_calling_code( $base_country ) : '';

			// remove plus sign if present
			return preg_replace( '/[^0-9]/', '', $calling_code );
		}

		return '';
	}
	/**
	 * Get customer phone number from order
	 *
	 * @since 4.1.2
	 *
	 * @param WC_Order $order Order object
	 * @param string $type Type of phone number ('billing', 'shipping', or 'both')
	 * @return array Array of formatted phone numbers
	 */
	public static function get_customer_phone_number_from_order( $order, $type = 'billing' ) {
		$phones = array();

		switch ( $type ) {
			case 'billing':
				$phone = $order->get_billing_phone();
				if ( ! empty( $phone ) ) {
					$country_code = $order->get_billing_country();
					$phones[] = self::format_phone_number( $phone, $country_code );
				}
				break;

			case 'shipping':
				$phone = $order->get_shipping_phone();
				if ( ! empty( $phone ) ) {
					$country_code = $order->get_shipping_country();
					$phones[] = self::format_phone_number( $phone, $country_code );
				}
				break;

			case 'both':
				$phone = $order->get_billing_phone();
				if ( ! empty( $phone ) ) {
					$phones[] = self::format_phone_number( $phone, $order->get_billing_country() );
				}
				$phone = $order->get_shipping_phone();
				if ( ! empty( $phone ) ) {
					$phones[] = self::format_phone_number( $phone, $order->get_shipping_country() );
				}
				break;
		}

		// filter out empty phone numbers
		$phones = array_filter(
			$phones,
			function( $phone_number ) {
				return ! empty( $phone_number );
			}
		);

		// remove duplicates
		$phones = array_unique( $phones );

		return $phones;
	}

	/**
	 * Format full address
	 *
	 * @since 4.1.2
	 *
	 * @param array $address Address array
	 * @return string Formatted address
	 */
	public static function format_full_address( $address ) {
		$default_args = array(
			'first_name' => '',
			'last_name'  => '',
			'company'    => '',
			'address_1'  => '',
			'address_2'  => '',
			'city'       => '',
			'state'      => '',
			'postcode'   => '',
			'country'    => '',
		);
		$address = array_map( 'trim', wp_parse_args( $address, $default_args ) );
		// Handle full country name.
		$address['country']  = self::get_country_full_name( $address['country'] );
		$address['state'] = self::get_state_full_name( $address['country'], $address['state'] );

		$parts = array(
			'full_name' => trim( $address['first_name'] . ' ' . $address['last_name'] ),
			'company'   => $address['company'],
			'address_1' => $address['address_1'],
			'address_2' => $address['address_2'],
			'city'      => $address['city'],
			'state'     => self::get_state_full_name( $address['country'], $address['state'] ),
			'postcode'  => trim( $address['postcode'] ),
			'country'   => self::get_country_full_name( $address['country'] ),
		);

		// remove empty parts
		$parts = array_filter(
			$parts,
			function( $part ) {
				return ! empty( $part );
			}
		);

		// join all address parts with comma
		$formatted_address = implode( ', ', $parts );

		return $formatted_address;
	}

	/**
	 * Get country full name
	 *
	 * @since 4.1.2
	 *
	 * @param string $country_code ISO country code
	 * @return string Country full name
	 */
	public static function get_country_full_name( $country_code ) {
		if ( class_exists( 'WC_Countries' ) && $country_code ) {
			$wc_countries = new \WC_Countries();
			return isset( $wc_countries->countries[ $country_code ] ) ? $wc_countries->countries[ $country_code ] : $country_code;
		}
		return $country_code;
	}

	/**
	 * Get state full name
	 *
	 * @since 4.1.2
	 *
	 * @param string $country_code ISO country code
	 * @param string $state_code ISO state code
	 * @return string State full name
	 */
	public static function get_state_full_name( $country_code, $state_code ) {
		if ( class_exists( 'WC_Countries' ) && $country_code && $state_code ) {
			$wc_countries = new \WC_Countries();
			return isset( $wc_countries->states[ $country_code ][ $state_code ] ) ? $wc_countries->states[ $country_code ][ $state_code ] : $state_code;
		}
		return $state_code;
	}

	/**
	 * Get the replacement variables for the WhatsApp template
	 *
	 * @since 4.1.2
	 *
	 * @param \WC_Order $order Order object
	 * @param AbandonedCart $cart Abandoned cart object
	 * @return array Array of replacements
	 */
	public static function get_replacement_variables( $order, $cart ) {
		// Base variables that don't require an order or cart
		$replacements = array(
			'site_title'   => get_bloginfo( 'name' ),
			'site_url'     => get_bloginfo( 'url' ),
			'shop_url'     => get_permalink( wc_get_page_id( 'shop' ) ),
			'account_url'  => get_permalink( wc_get_page_id( 'myaccount' ) ),
			'checkout_url' => wc_get_checkout_url(),
			'cart_url'     => wc_get_cart_url(),
		);

		$cart_variables = array();
		if ( ! empty( $cart ) && is_object( $cart ) ) {
			$cart_variables = $cart->get_cart_variables();
		}

		$order_variables = array();
		if ( ! empty( $order ) && is_object( $order ) ) {
			$order_variables = array(
				'order_id'            => $order->get_order_number(),
				'order_total'         => $order->get_total(),
				'order_items_count'   => $order->get_item_count(),
				'order_currency'      => $order->get_currency(),
				'order_date'          => $order->get_date_created()->date_i18n( get_option( 'date_format' ) ),
				'order_url'           => $order->get_view_order_url(),
				'order_admin_url'     => $order->get_edit_order_url(),
				'billing_first_name'  => $order->get_billing_first_name(),
				'billing_last_name'   => $order->get_billing_last_name(),
				'billing_full_name'   => $order->get_formatted_billing_full_name(),
				'billing_company'     => $order->get_billing_company(),
				'billing_address_1'   => $order->get_billing_address_1(),
				'billing_address_2'   => $order->get_billing_address_2(),
				'billing_city'        => $order->get_billing_city(),
				'billing_state'       => self::get_state_full_name( $order->get_billing_country(), $order->get_billing_state() ),
				'billing_postcode'    => $order->get_billing_postcode(),
				'billing_country'     => self::get_country_full_name( $order->get_billing_country() ),
				'billing_phone'       => $order->get_billing_phone(),
				'billing_email'       => $order->get_billing_email(),
				'billing_address'     => self::format_full_address( $order->get_address( 'billing' ) ),

				'shipping_first_name' => $order->get_shipping_first_name(),
				'shipping_last_name'  => $order->get_shipping_last_name(),
				'shipping_full_name'  => $order->get_formatted_shipping_full_name(),
				'shipping_address_1'  => $order->get_shipping_address_1(),
				'shipping_address_2'  => $order->get_shipping_address_2(),
				'shipping_city'       => $order->get_shipping_city(),
				'shipping_state'      => self::get_state_full_name( $order->get_shipping_country(), $order->get_shipping_state() ),
				'shipping_postcode'   => $order->get_shipping_postcode(),
				'shipping_country'    => self::get_country_full_name( $order->get_shipping_country() ),
				'shipping_phone'      => $order->get_shipping_phone(),
				'shipping_address'    => self::format_full_address( $order->get_address( 'shipping' ) ),
			);
		}

		// Merge the arrays with order and cart variables taking precedence
		return array_merge( $replacements, $order_variables, $cart_variables );
	}

	/**
	 * Replace placeholders enclosed in double curly braces {{placeholder}} with values from replacements
	 *
	 * @since 4.1.2
	 *
	 * @param string $text Text containing placeholders in {{placeholder}} format
	 * @param array $replacements Array of replacement values
	 * @return string Text with placeholders replaced
	 */
	public static function replace_placeholders( $text, $replacements ) {
		// Regular expression to match {{placeholder}} format
		$pattern = '/{{([^{}]+)}}/';

		$value = preg_replace_callback(
			$pattern,
			function( $matches ) use ( $replacements ) {
				$placeholder = trim( $matches[1] );

				// Check if the placeholder exists in replacements
				if ( isset( $replacements[ $placeholder ] ) ) {
					return $replacements[ $placeholder ];
				}

				// Return a single space if no replacement found
				return ' ';
			},
			$text
		);

		// Replace new-line/tab characters with a single space
		$value = preg_replace( '/[\n\t]/', ' ', $value );

		// Replace more than 4 consecutive spaces with 2 spaces
		$value = preg_replace( '/\s{5,}/', '  ', $value );

		// If the value is empty, return a single space otherwise
		// whatsapp will not send the message if the value is empty
		// and return an error
		if ( '' === $value ) {
			$value = ' ';
		}

		return $value;
	}

	/**
	 * Determine the parameter format type for WhatsApp templates
	 *
	 * @since 4.1.2
	 *
	 * @param array $header_variables Header variables configuration
	 * @param array $body_variables Body variables configuration
	 * @return string Parameter format ('POSITIONAL' or 'NAMED')
	 */
	public static function get_template_parameter_format( $header_variables, $body_variables ) {
		$header_vars = isset( $header_variables ) ? $header_variables : array();
		$body_vars = isset( $body_variables ) ? $body_variables : array();

		// Check both header and body variables
		$variables = array_merge( $header_vars, $body_vars );

		if ( empty( $variables ) ) {
			return 'POSITIONAL'; // Default to positional if no variables
		}

		// Check if any variable has a 'key' property that is NOT numeric
		// If we find any non-numeric key, it's a NAMED parameter format
		foreach ( $variables as $variable ) {
			if ( isset( $variable['key'] ) && ! is_numeric( $variable['key'] ) ) {
				return 'NAMED';
			}
		}

		// All keys are numeric or not set, so it's POSITIONAL
		return 'POSITIONAL';
	}

	/**
	 * Format WhatsApp error message
	 *
	 * @since 4.1.2
	 *
	 * @param string $phone_number Phone number
	 * @param array|Exception $result Result array or Exception object
	 * @return string Formatted error message
	 */
	public static function format_whatsapp_error_message( $phone_number, $result ) {
		$error_message = 'Unknown error';

		// if result is an exception, get the message
		if ( $result instanceof \Exception ) {
			$error_message = $result->getMessage();
		} elseif ( is_array( $result ) && isset( $result['error'] ) && is_array( $result['error'] ) ) {
			// Check if error key exists and has message
			if ( isset( $result['error']['message'] ) ) {
				$error_message = $result['error']['message'];
			}

			// Check if additional error details exist
			if ( isset( $result['error']['error_data'] ) &&
			isset( $result['error']['error_data']['details'] ) ) {
				$error_message .= ' - ' . $result['error']['error_data']['details'];
			}
		}

		return $phone_number . '(' . $error_message . ')';
	}


}

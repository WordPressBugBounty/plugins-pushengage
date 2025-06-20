<?php
namespace Pushengage\Utils;


/**
 * Class responsible for encrypting and decrypting data.
 *
 * @since 4.1.2
 */
class Encryption {

	/**
	 * Key to use for encryption.
	 *
	 * @since 4.1.2
	 * @var string
	 */
	private $key;

	/**
	 * Method to use for encryption.
	 *
	 * @since 4.1.2
	 * @var string
	 */
	private $method = 'aes-256-ctr';

	/**
	 * Constructor.
	 *
	 * @since 4.1.2
	 * @param string $key Encryption key.
	 */
	public function __construct( $key = '' ) {
		if ( ! empty( $key ) && is_string( $key ) ) {
			$this->key = $key;
		} else {
			$this->key = $this->get_default_encryption_key();
		}
	}

	/**
	 * Gets the default encryption key to use.
	 *
	 * @return string Default encryption key.
	 * @since 4.1.2
	 */
	private function get_default_encryption_key() {
		// Check for user-defined constants first
		if ( defined( '\PUSHENGAGE_ENCRYPTION_KEY' ) && '' !== \PUSHENGAGE_ENCRYPTION_KEY ) {
			return \PUSHENGAGE_ENCRYPTION_KEY;
		}

		// Get encryption config from database if not set by user
		$encryption_key = get_option( 'pushengage_encryption_key' );

		if ( empty( $encryption_key ) ) {
			// Generate new key if not set by user and save to database
			$encryption_key = $this->generate_secure_key();
			update_option( 'pushengage_encryption_key', $encryption_key );
		}

		return $encryption_key;
	}

	/**
	 * Generates a cryptographically secure key.
	 *
	 * @return string Secure encryption key.
	 * @since 4.1.2
	 */
	private function generate_secure_key() {
		if ( function_exists( '\openssl_random_pseudo_bytes' ) ) {
			return base64_encode( \openssl_random_pseudo_bytes( 32 ) );
		}

		if ( function_exists( '\random_bytes' ) ) {
			return base64_encode( \random_bytes( 32 ) );
		}

		// Fallback to wp_generate_password
		return wp_generate_password( 64, true, true );
	}

	/**
	 * Checks if openssl is enabled and key is set.
	 *
	 * @return bool True if encryption is available, false otherwise.
	 * @since 4.1.2
	 */
	private function is_encryption_available() {

		if ( ! extension_loaded( 'openssl' ) ) {
			return false;
		}

		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return false;
		}

		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return false;
		}

		if ( empty( $this->key ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Encrypts data using the current key.
	 *
	 * @param string $data Data to encrypt.
	 * @return string|false Encrypted data or false on failure.
	 * @since 4.1.2
	 */
	public function encrypt( $data ) {
		if ( empty( $data ) ) {
			return $data;
		}

		if ( ! is_string( $data ) ) {
			return false;
		}

		if ( ! $this->is_encryption_available() ) {
			return false;
		}

		$iv_len = openssl_cipher_iv_length( $this->method );
		$iv = openssl_random_pseudo_bytes( $iv_len );

		$encrypted = openssl_encrypt( $data, $this->method, $this->key, OPENSSL_RAW_DATA, $iv );

		if ( false === $encrypted ) {
			return false;
		}

		// Combine IV and encrypted data
		$combined = $iv . $encrypted;

		return base64_encode( $combined );
	}

	/**
	 * Decrypts data using the current key.
	 *
	 * @param string $encrypted_data Encrypted data to decrypt.
	 * @return string|false Decrypted data or false on failure.
	 * @since 4.1.2
	 */
	public function decrypt( $encrypted_data ) {
		if ( empty( $encrypted_data ) ) {
			return $encrypted_data;
		}

		if ( ! is_string( $encrypted_data ) ) {
			return false;
		}

		if ( ! $this->is_encryption_available() ) {
			return false;
		}

		$decoded = base64_decode( $encrypted_data, true );

		if ( false === $decoded ) {
			return false;
		}

		$iv_len = openssl_cipher_iv_length( $this->method );

		if ( strlen( $decoded ) < $iv_len ) {
			return false;
		}

		$iv = substr( $decoded, 0, $iv_len );
		$encrypted = substr( $decoded, $iv_len );

		$decrypted = openssl_decrypt( $encrypted, $this->method, $this->key, OPENSSL_RAW_DATA, $iv );

		if ( false === $decrypted ) {
			return false;
		}

		return $decrypted;
	}

	/**
	 * Gets the current encryption key (for debugging/logging purposes).
	 *
	 * @return string Current encryption key.
	 * @since 4.1.2
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Gets the current encryption method.
	 *
	 * @return string Current encryption method.
	 * @since 4.1.2
	 */
	public function get_method() {
		return $this->method;
	}
}

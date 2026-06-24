<?php
namespace Pushengage\Utils;


/**
 * Class responsible for encrypting and decrypting data.
 *
 * Encryption uses AES-256-GCM (AEAD). Legacy ciphertext written by
 * earlier plugin versions used unauthenticated AES-256-CTR; `decrypt()`
 * still accepts that format so existing stored values keep working
 * across the upgrade. The next save through `encrypt()` re-writes the
 * value in the AEAD format.
 *
 * Ciphertext layout written by this class:
 *   base64( 'g1' . IV(12) . TAG(16) . CIPHERTEXT )
 *
 * Legacy layout still accepted on decrypt:
 *   base64( IV(16) . CIPHERTEXT )                  // AES-256-CTR
 *
 * @since 4.1.2
 */
class Encryption {

	/**
	 * Key to use for encryption (binary, 32 bytes).
	 *
	 * @since 4.1.2
	 * @var string
	 */
	private $key;

	/**
	 * AEAD cipher used for new ciphertext.
	 *
	 * @since 4.2.6
	 * @var string
	 */
	private $cipher = 'aes-256-gcm';

	/**
	 * Legacy cipher accepted on decrypt for backward compatibility.
	 *
	 * @since 4.2.6
	 * @var string
	 */
	private $legacy_cipher = 'aes-256-ctr';

	/**
	 * Magic version header for the new AEAD format.
	 *
	 * @since 4.2.6
	 * @var string
	 */
	const FORMAT_TAG_GCM = 'g1';

	/**
	 * Length, in bytes, of the GCM IV.
	 *
	 * @since 4.2.6
	 * @var int
	 */
	const GCM_IV_LEN = 12;

	/**
	 * Length, in bytes, of the GCM auth tag.
	 *
	 * @since 4.2.6
	 * @var int
	 */
	const GCM_TAG_LEN = 16;

	/**
	 * Constructor.
	 *
	 * The key is stored verbatim. `openssl_encrypt`/`openssl_decrypt`
	 * truncate keys longer than the cipher's expected length (32 bytes
	 * for AES-256), so a 44-character base64 string (the format
	 * `generate_secure_key()` emits and `pushengage_encryption_key`
	 * stores) is consumed as its first 32 ASCII characters. Older
	 * plugin versions wrote ciphertext using exactly that convention, so
	 * passing the key through unchanged keeps legacy ciphertext
	 * decryptable after the upgrade.
	 *
	 * @since 4.1.2
	 * @param string $key Encryption key. Empty / non-string falls back
	 *                    to the stored / user-defined default key.
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
	 * Prefers `random_bytes()` (which is always CSPRNG-backed) over
	 * `openssl_random_pseudo_bytes()` (whose strong-output flag was
	 * historically ignored by this code) so the generated key is always
	 * suitable for cryptographic use.
	 *
	 * @return string Base64-encoded 32 random bytes, or — only if both
	 *                random_bytes() and a strong openssl source are
	 *                unavailable — a 64-character random string from the
	 *                wp_generate_password() fallback.
	 * @since 4.1.2
	 */
	private function generate_secure_key() {
		if ( function_exists( '\random_bytes' ) ) {
			try {
				return base64_encode( \random_bytes( 32 ) );
			} catch ( \Exception $e ) {
				// fall through to next source on RNG failure.
			}
		}

		if ( function_exists( '\openssl_random_pseudo_bytes' ) ) {
			$strong = false;
			$bytes  = \openssl_random_pseudo_bytes( 32, $strong );
			if ( false !== $bytes && true === $strong ) {
				return base64_encode( $bytes );
			}
		}

		// Last-resort fallback: wp_generate_password ultimately uses
		// wp_rand(), which on modern WP is CSPRNG-backed.
		return wp_generate_password( 64, true, true );
	}

	/**
	 * Verifies that OpenSSL is loaded, AES-256-GCM is available on this
	 * PHP/OpenSSL build, and the key is set.
	 *
	 * The plugin's minimum PHP version is 7.4. The 6-argument form of
	 * `openssl_encrypt()` (with `$tag` and `$aad`) has been available
	 * since PHP 7.1, and the AES-GCM cipher is present when linked
	 * against OpenSSL 1.0.1+. `encrypt()` fails closed if GCM is not
	 * present at runtime, rather than silently downgrading to an
	 * unauthenticated cipher.
	 *
	 * @return bool True if AEAD encryption is available, false otherwise.
	 * @since 4.1.2
	 */
	private function is_encryption_available() {

		if ( ! extension_loaded( 'openssl' ) ) {
			return false;
		}

		if ( ! function_exists( 'openssl_encrypt' ) || ! function_exists( 'openssl_decrypt' ) ) {
			return false;
		}

		if ( empty( $this->key ) ) {
			return false;
		}

		static $has_gcm = null;
		if ( null === $has_gcm ) {
			$ciphers = function_exists( 'openssl_get_cipher_methods' )
				? array_map( 'strtolower', openssl_get_cipher_methods() )
				: array();
			$has_gcm = in_array( $this->cipher, $ciphers, true );
		}

		return $has_gcm;
	}

	/**
	 * Encrypts data using AES-256-GCM.
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

		$iv = false;
		try {
			$iv = \random_bytes( self::GCM_IV_LEN );
		} catch ( \Exception $e ) {
			// random_bytes() only throws when no CSPRNG is available. Fall
			// back to openssl, but only accept its output when it reports a
			// cryptographically strong result — otherwise fail closed rather
			// than risk a weak GCM nonce.
			$strong = false;
			$iv     = openssl_random_pseudo_bytes( self::GCM_IV_LEN, $strong );
			if ( true !== $strong ) {
				$iv = false;
			}
		}

		if ( false === $iv || strlen( $iv ) !== self::GCM_IV_LEN ) {
			return false;
		}

		$tag       = '';
		$encrypted = openssl_encrypt( $data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $tag, '', self::GCM_TAG_LEN );

		if ( false === $encrypted || self::GCM_TAG_LEN !== strlen( $tag ) ) {
			return false;
		}

		// On-the-wire format: "g1" || IV(12) || TAG(16) || CIPHERTEXT.
		// The format tag lets decrypt() distinguish AEAD ciphertext from
		// the legacy AES-256-CTR ciphertext that earlier plugin versions
		// wrote; CTR is still accepted on the read path for migration,
		// but encrypt() never produces it.
		return base64_encode( self::FORMAT_TAG_GCM . $iv . $tag . $encrypted );
	}

	/**
	 * Decrypts data.
	 *
	 * Accepts both the current AEAD (GCM) format AND the legacy
	 * unauthenticated CTR format written by versions <= 4.2.5. Legacy
	 * values keep working seamlessly; the next call to encrypt() (which
	 * happens on the next save through update_whatsapp_settings et al.)
	 * upgrades them to the AEAD format.
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

		if ( ! extension_loaded( 'openssl' ) || ! function_exists( 'openssl_decrypt' ) || empty( $this->key ) ) {
			return false;
		}

		$decoded = base64_decode( $encrypted_data, true );

		if ( false === $decoded ) {
			return false;
		}

		// New AEAD format: "g1" || IV(12) || TAG(16) || CIPHERTEXT.
		$tag_len = strlen( self::FORMAT_TAG_GCM );
		if ( strlen( $decoded ) > $tag_len && substr( $decoded, 0, $tag_len ) === self::FORMAT_TAG_GCM ) {
			$min_len = $tag_len + self::GCM_IV_LEN + self::GCM_TAG_LEN;
			if ( strlen( $decoded ) <= $min_len ) {
				return false;
			}

			$iv         = substr( $decoded, $tag_len, self::GCM_IV_LEN );
			$auth_tag   = substr( $decoded, $tag_len + self::GCM_IV_LEN, self::GCM_TAG_LEN );
			$ciphertext = substr( $decoded, $min_len );

			$plaintext = openssl_decrypt( $ciphertext, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $auth_tag );
			return false === $plaintext ? false : $plaintext;
		}

		// Legacy format: IV(16) || CIPHERTEXT under AES-256-CTR. No
		// authentication, but accepted on read-only paths so prior
		// installs upgrade without admins having to re-enter credentials.
		$legacy_iv_len = openssl_cipher_iv_length( $this->legacy_cipher );
		if ( false === $legacy_iv_len || strlen( $decoded ) <= $legacy_iv_len ) {
			return false;
		}

		$legacy_iv         = substr( $decoded, 0, $legacy_iv_len );
		$legacy_ciphertext = substr( $decoded, $legacy_iv_len );
		$plaintext         = openssl_decrypt( $legacy_ciphertext, $this->legacy_cipher, $this->key, OPENSSL_RAW_DATA, $legacy_iv );

		return false === $plaintext ? false : $plaintext;
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
		return $this->cipher;
	}
}

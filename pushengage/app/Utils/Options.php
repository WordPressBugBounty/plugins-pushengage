<?php
namespace Pushengage\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Options {
	/**
	 * internal cache for site settings option
	 *
	 * @var $array
	 */
	private static $site_settings;

	/**
	 * internal cache for whatsapp settings option
	 *
	 * @var $array
	 */
	private static $whatsapp_settings;

	/**
	 * internal cache for whatsapp automation campaigns option
	 *
	 * @var $array
	 */
	private static $whatsapp_automation_campaigns;

	/**
	 * Get Pushengage Settings Options
	 *
	 * @since 4.0.5
	 *
	 * @return array
	 */
	public static function get_site_settings() {
		if ( empty( self::$site_settings ) ) {
			$settings = get_option( 'pushengage_settings', array() );

			// Set default values for misc settings if not set
			$defaults = array(
				'hideAdminBarMenu'       => false,
				'hideDashboardWidget'    => false,
				'enableDebugMode'        => false,
				'enableWpMetricsTracker' => true,
				'debugLevel'             => 'debug',
			);

			foreach ( $defaults as $key => $value ) {
				if ( ! isset( $settings['misc'][ $key ] ) ) {
					$settings['misc'][ $key ] = $value;
				}
			}

			update_option( 'pushengage_settings', $settings );

			self::$site_settings = $settings;
		}

		return self::$site_settings;
	}

	/**
	 * Update pushengage settings Options
	 *
	 * @since 4.0.5
	 *
	 * @return bool
	 */
	public static function update_site_settings( $data ) {
		// clear the internal cache for site settings
		self::$site_settings = array();
		return update_option( 'pushengage_settings', $data );
	}

	/**
	 * Check if site is connected, if so then we have credentials, otherwise false
	 *
	 * @since 4.0.5
	 *
	 * @return boolean
	 */
	public static function has_credentials() {
		$pushengage_settings = self::get_site_settings();
		if (
			! empty( $pushengage_settings['api_key'] )
			&& ! empty( $pushengage_settings['site_id'] )
			&& ! empty( $pushengage_settings['site_key'] )
			&& ! empty( $pushengage_settings['owner_id'] )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Get all the post types which are allowed for auto push
	 *
	 * @since 4.0.5
	 *
	 * @return array
	 */
	public static function get_allowed_post_types_for_auto_push() {
		$pushengage_settings = self::get_site_settings();
		if ( isset( $pushengage_settings['allowed_post_types'] ) ) {
			$decoded = json_decode( $pushengage_settings['allowed_post_types'], true );

			// Legacy/staging data may contain a mixed array of strings and
			// {label, value} objects. Project everything to a flat list of
			// post-type slugs and drop anything we can't make sense of.
			$slugs = array();
			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $item ) {
					if ( is_string( $item ) ) {
						$slugs[] = $item;
					} elseif ( is_array( $item ) && isset( $item['value'] ) && is_string( $item['value'] ) ) {
						$slugs[] = $item['value'];
					}
				}
			}

			// Mixed string + {value: …} entries for the same slug otherwise
			// produce duplicates (e.g. ['post', {value: 'post'}] → ['post', 'post']).
			return array_values( array_unique( $slugs ) );
		}

		$args = array(
			'public' => true,
		);

		return array_values( get_post_types( $args ) );
	}

	/**
	 * Get WhatsApp Settings Options
	 *
	 * @since 4.0.9
	 *
	 * @return array
	 */
	public static function get_whatsapp_settings() {
		if ( empty( self::$whatsapp_settings ) ) {
			$settings = get_option( 'pushengage_whatsapp_settings', array() );

			// may be decrypt the encrypted access key
			if ( isset( $settings['accessToken'] ) && ! empty( $settings['accessToken'] ) ) {
				$encryption = new Encryption();
				$decrypted_access_token = $encryption->decrypt( $settings['accessToken'] );
				if ( ! empty( $decrypted_access_token ) ) {
					$settings['accessToken'] = $decrypted_access_token;
				}
				$access_token_hash = hash( 'sha256', $settings['accessToken'] );
				// Timing-safe comparison; the value isn't network-attacker
				// reachable here, but using hash_equals matches the rest of
				// the codebase's hardening posture and avoids accidental
				// timing leaks if this helper is ever moved closer to a
				// request boundary.
				$settings['isDecryptedAccessTokenValid'] = isset( $settings['accessTokenHash'] )
					&& is_string( $settings['accessTokenHash'] )
					&& hash_equals( $settings['accessTokenHash'], $access_token_hash );

				// A prior Meta Cloud API 401 (token revoked/expired) flags the
				// stored token as invalid out-of-band. Honor that here so the
				// existing "credentials invalid" notice and the send guard react
				// without WhatsappCloudApi ever having to rewrite the encrypted
				// settings struct (which previously leaked the decrypted token
				// to wp_options in plaintext and corrupted accessTokenHash).
				if ( get_option( 'pushengage_whatsapp_access_token_invalid', false ) ) {
					$settings['isDecryptedAccessTokenValid'] = false;
				}
			}

			self::$whatsapp_settings = $settings;
		}

		return self::$whatsapp_settings;
	}

	/**
	 * Update WhatsApp Settings Options
	 *
	 * @since 4.0.9
	 *
	 * @param array $data Settings data to update
	 * @return bool
	 */
	public static function update_whatsapp_settings( $data ) {
		// clear the internal cache for whatsapp settings
		self::$whatsapp_settings = array();

		// Defense-in-depth schema allowlist. The Ajax handler already
		// sanitizes per-field, but a future caller hitting this static
		// directly should not be able to seed unrelated keys into the
		// option (and accidentally shadow other settings or surface as
		// attributes in our own getters).
		$allowed_keys = array(
			'whatsappBusinessId'  => true,
			'whatsappPhoneNumber' => true,
			'phoneNumberId'       => true,
			'accessToken'         => true,
			'accessTokenHash'     => true,
		);
		$data = is_array( $data ) ? array_intersect_key( $data, $allowed_keys ) : array();

		if ( isset( $data['accessToken'] ) && ! empty( $data['accessToken'] ) ) {
			$data['accessTokenHash'] = hash( 'sha256', $data['accessToken'] );
			$encryption = new Encryption();
			$encrypted_access_token = $encryption->encrypt( $data['accessToken'] );

			// Fail closed: a falsy return from encrypt() means OpenSSL /
			// AES-256-GCM is unavailable on this PHP build, the key was
			// missing, or the cipher call itself failed. The pre-4.2.6
			// behavior here was to silently store the access token in
			// plaintext, which leaks WhatsApp Cloud API credentials to
			// anyone with DB read (other plugin, backup snapshot, SQLi).
			// Refuse the save instead so the admin sees an error and the
			// token never lands on disk unencrypted.
			if ( empty( $encrypted_access_token ) ) {
				return false;
			}

			$data['accessToken'] = $encrypted_access_token;
		}

		$result = update_option( 'pushengage_whatsapp_settings', $data );

		// A successful save means the admin re-entered credentials, so clear any
		// prior Meta 401 "token invalid" flag. This resets both the admin notice
		// and the send guard that read it via get_whatsapp_settings().
		if ( $result ) {
			delete_option( 'pushengage_whatsapp_access_token_invalid' );
		}

		return $result;
	}

	/**
	 * Flag the stored WhatsApp access token as invalid.
	 *
	 * Used when Meta's Cloud API rejects the token (e.g. a 401 from a revoked or
	 * expired token). The flag lives in its own option so callers that hold the
	 * decrypted token never have to rewrite the encrypted settings struct just to
	 * record an invalid state. get_whatsapp_settings() reads it to drive the
	 * "credentials invalid" admin notice and the send guard.
	 *
	 * @since 4.2.6
	 *
	 * @param bool $invalid Whether the token is invalid. Defaults to true.
	 * @return void
	 */
	public static function set_whatsapp_access_token_invalid( $invalid = true ) {
		// Clear the internal cache so the next get_whatsapp_settings() call
		// reflects the new state.
		self::$whatsapp_settings = array();

		if ( $invalid ) {
			update_option( 'pushengage_whatsapp_access_token_invalid', true );
		} else {
			delete_option( 'pushengage_whatsapp_access_token_invalid' );
		}
	}

	/**
	 * Get WhatsApp Automation Campaigns
	 *
	 * @since 4.0.9
	 *
	 * @return array
	 */
	public static function get_whatsapp_automation_campaigns() {
		if ( empty( self::$whatsapp_automation_campaigns ) ) {
			$campaigns = get_option( 'pushengage_wa_automation_campaigns', array() );
			self::$whatsapp_automation_campaigns = $campaigns;
		}

		return self::$whatsapp_automation_campaigns;
	}

	/**
	 * Update WhatsApp Automation Campaigns
	 *
	 * @since 4.0.9
	 *
	 * @param array $campaigns Campaign data to update
	 * @return bool
	 */
	public static function update_whatsapp_automation_campaigns( $campaigns ) {
		// Clear the internal cache
		self::$whatsapp_automation_campaigns = array();
		return update_option( 'pushengage_wa_automation_campaigns', $campaigns );
	}

	/**
	 * Get WhatsApp Automation Campaign by ID
	 *
	 * @since 4.0.9
	 *
	 * @param string $campaign_id Campaign ID to get
	 * @return array|null Campaign data or null if not found
	 */
	public static function get_whatsapp_automation_campaign( $campaign_id ) {
		$campaigns = self::get_whatsapp_automation_campaigns();

		if ( isset( $campaigns[ $campaign_id ] ) ) {
			return $campaigns[ $campaign_id ];
		}

		return null;
	}

	/**
	 * Update WhatsApp Automation Campaign
	 *
	 * @since 4.0.9
	 *
	 * @param array $campaign Campaign data to update
	 * @return bool
	 */
	public static function update_whatsapp_automation_campaign( $campaign ) {
		if ( empty( $campaign['id'] ) ) {
			return false;
		}

		$campaigns = self::get_whatsapp_automation_campaigns();
		$campaigns[ $campaign['id'] ] = $campaign;

		return self::update_whatsapp_automation_campaigns( $campaigns );
	}

	/**
	 * Get WhatsApp Click To Chat Settings
	 *
	 * @since 4.1.0
	 *
	 * @return array
	 */
	public static function get_whatsapp_click_to_chat_settings() {
		$settings = get_option( 'pushengage_whatsapp_click_to_chat', array() );

		// Default settings
		$defaults = array(
			'enabled'          => false,
			'phoneNumber'      => '',
			'greetingMessage'  => 'Hi there!',
			'buttonStyle'      => 'style1',
			'buttonSize'       => 48,
			'buttonPosition'   => 'bottom-right',
			'horizontalOffset' => 20,
			'verticalOffset'   => 20,
			'zIndex'           => 9999,
		);

		// Return default settings if empty, otherwise merge with defaults
		if ( empty( $settings ) ) {
			return $defaults;
		}

		// Merge with defaults to ensure all keys exist
		return array_merge( $defaults, $settings );
	}

	/**
	 * Update WhatsApp Click To Chat Settings
	 *
	 * @since 4.1.0
	 *
	 * @param array $data Settings data to update
	 * @return bool
	 */
	public static function update_whatsapp_click_to_chat_settings( $data ) {
		// Defense-in-depth schema allowlist; the Ajax handler also
		// constructs a fixed-key $data array, but enforce it at the
		// storage boundary too.
		$allowed_keys = array(
			'enabled'          => true,
			'phoneNumber'      => true,
			'greetingMessage'  => true,
			'buttonStyle'      => true,
			'buttonSize'       => true,
			'buttonPosition'   => true,
			'horizontalOffset' => true,
			'verticalOffset'   => true,
			'zIndex'           => true,
		);
		$data = is_array( $data ) ? array_intersect_key( $data, $allowed_keys ) : array();

		return update_option( 'pushengage_whatsapp_click_to_chat', $data );
	}

		/**
	 * Get Push Notification Automation Settings
	 *
	 * @since 4.1.4
	 *
	 * @return array
	 */
	public static function get_push_notification_automation_settings() {
		// Get site settings.
		$site_settings = self::get_site_settings();

		// Get push notification events from NotificationSettings
		$notification_events = \Pushengage\Integrations\WooCommerce\NotificationSettings::get_push_notification_events();

		$push_notification_settings = array();

		$campaigns_mapping = array(
			'new_order'        => 'new_order',
			'cancelled_order'  => 'order_cancelled',
			'failed_order'     => 'order_failed',
			'order_on_hold'    => 'order_on_hold',
			'processing_order' => 'order_processing',
			'completed_order'  => 'order_completed',
			'refunded_order'   => 'order_refunded',
			'order_details'    => 'order_details',
			'customer_note'    => 'customer_note',
			'review_request'   => 'review_request',
			'retry_purchase'   => 'retry_purchase_request',
		);

		// Process each notification event
		foreach ( $notification_events as $event_id => $event_data ) {
			// Get individual notification settings for this event
			$notification_settings = get_option( 'pe_notification_' . $event_id, array() );

			// Get template defaults
			$template_defaults = isset( \Pushengage\Integrations\WooCommerce\NotificationTemplates::$templates[ $event_id ] )
				? \Pushengage\Integrations\WooCommerce\NotificationTemplates::$templates[ $event_id ]
				: array();

			// Check admin notification settings
			$admin_enabled = isset( $notification_settings['enable_admin'] )
				? $notification_settings['enable_admin']
				: ( isset( $template_defaults['enable_admin'] ) ? $template_defaults['enable_admin'] : 'no' );

			// Check customer notification settings
			$customer_enabled = isset( $notification_settings['enable_customer'] )
				? $notification_settings['enable_customer']
				: ( isset( $template_defaults['enable_customer'] ) ? $template_defaults['enable_customer'] : 'no' );

			// Set the settings based on individual admin and customer enabled states
			$push_notification_settings[ $campaigns_mapping[ $event_id ] ] = array(
				'admin'    => ( 'yes' === $admin_enabled ) ? 1 : 0,
				'customer' => ( 'yes' === $customer_enabled ) ? 1 : 0,
			);
		}

		// Add cart/browse abandonment campaign settings. Use empty() to safely
		// traverse — `woo_integration` only exists once the user has configured
		// WooCommerce automations via the admin UI; on fresh installs or
		// non-WooCommerce sites the key is absent.
		$push_notification_settings['cart_abandonment']    = ! empty( $site_settings['woo_integration']['cart_abandonment']['enable'] ) ? 1 : 0;
		$push_notification_settings['browse_abandonment']  = ! empty( $site_settings['woo_integration']['browse_abandonment']['enable'] ) ? 1 : 0;

		return $push_notification_settings;
	}

	/**
	 * Get the role slugs allowed to access the post-editor Auto Push UI.
	 *
	 * @since 4.2.5
	 *
	 * @return string[]
	 */
	public static function get_auto_push_allowed_roles() {
		$settings = self::get_site_settings();
		$roles    = isset( $settings['auto_push_allowed_roles'] ) ? $settings['auto_push_allowed_roles'] : array();
		if ( ! is_array( $roles ) ) {
			return array();
		}
		// Filter to currently-existing roles; drops removed/renamed custom roles.
		$existing = array_keys( wp_roles()->roles );
		return array_values( array_intersect( $roles, $existing ) );
	}

	/**
	 * Return a copy of site settings with credential-bearing keys stripped.
	 * Used when localizing settings to non-credentialed contexts (post editor).
	 *
	 * @since 4.2.5
	 *
	 * @return array
	 */
	public static function get_safe_site_settings() {
		$settings = self::get_site_settings();
		if ( ! is_array( $settings ) ) {
			return array();
		}
		$sensitive_exact = array( 'api_key', 'site_key', 'license_key', 'secret_key', 'secret' );
		$clean = array();
		foreach ( $settings as $key => $value ) {
			if ( in_array( $key, $sensitive_exact, true ) ) {
				continue;
			}
			if ( preg_match( '/_(secret|token|key)$/i', $key ) ) {
				continue;
			}
			$clean[ $key ] = $value;
		}
		return $clean;
	}
}

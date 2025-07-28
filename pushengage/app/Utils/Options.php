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
	 * @return $array
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
			return json_decode( $pushengage_settings['allowed_post_types'], true );
		}

		$args = array(
			'public' => true,
		);

		return get_post_types( $args );
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
				// add a valid flag to the settings if the decrypted access token hash is valid or not
				$settings['isDecryptedAccessTokenValid'] = $access_token_hash === $settings['accessTokenHash'];
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
		if ( isset( $data['accessToken'] ) && ! empty( $data['accessToken'] ) ) {
			$data['accessTokenHash'] = hash( 'sha256', $data['accessToken'] );
			$encryption = new Encryption();
			$encrypted_access_token = $encryption->encrypt( $data['accessToken'] );
			// if  encrypted access token is empty that means either access token is empty or encryption failed
			// in that store the original access token.
			if ( ! empty( $encrypted_access_token ) ) {
				$data['accessToken'] = $encrypted_access_token;
			}
		}
		return update_option( 'pushengage_whatsapp_settings', $data );
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

		// Add cart abandonment campaign settings.
		$push_notification_settings['cart_abandonment'] = $site_settings['woo_integration']['cart_abandonment']['enable'] ? 1 : 0;

		// Add browse abandonment campaign settings.
		$push_notification_settings['browse_abandonment'] = $site_settings['woo_integration']['browse_abandonment']['enable'] ? 1 : 0;

		return $push_notification_settings;
	}
}

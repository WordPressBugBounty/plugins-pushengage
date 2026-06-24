<?php

namespace Pushengage\Integrations\WooCommerce\Whatsapp;

use PushEngage\Utils\Options;

class WhatsappCloudApi {

	/**
	 * WhatsApp credentials
	 *
	 * @since 4.1.2
	 * @var array
	 */
	private $credentials;

	/**
	 * WhatsApp API base URL
	 *
	 * @since 4.1.2
	 * @var string
	 */
	private $api_base_url = 'https://graph.facebook.com/v22.0/';

	/**
	 * Constructor
	 *
	 * @since 4.1.2
	 */
	public function __construct() {
		$this->credentials = Options::get_whatsapp_settings();
	}

	/**
	 * Update message tracking transient data.
	 *
	 * @since 4.1.4
	 */
	private function update_message_tracking_transient() {
		$transient_key = 'pushengage_wp_metrics_whatsapp_tracking';
		$tracking_data = get_transient( $transient_key );
		$current_timestamp = time();

		if ( false === $tracking_data ) {
			// Initialize tracking data if not already set.
			$tracking_data = array(
				'first_message_ts' => $current_timestamp,
				'last_message_ts'  => $current_timestamp,
				'message_count'    => 1,
			);

			set_transient( $transient_key, $tracking_data, 30 * DAY_IN_SECONDS );
			return;
		}

		// Update tracking data.
		$tracking_data['last_message_ts']  = $current_timestamp;
		$tracking_data['message_count']    = (int) $tracking_data['message_count'] + 1;

		set_transient( $transient_key, $tracking_data, 30 * DAY_IN_SECONDS );
	}

	/**
	 * Send WhatsApp message using template
	 *
	 * @since 4.1.2
	 *
	 * @param string $to Recipient phone number
	 * @param string $template_name Template name
	 * @param string $template_language Template language
	 * @param array  $components Component parameters of a template
	 * @return array
	 */
	public function send_template_message( $to, $template_name, $template_language, $components = array() ) {
		if ( ! WhatsappHelper::is_valid_whatsapp_credentials( $this->credentials ) ) {
			return array(
				'success' => false,
				'error'   => array(
					'message' => __( 'WhatsApp credentials are missing or invalid', 'pushengage' ),
				),
			);
		}

		$url = $this->api_base_url . $this->credentials['phoneNumberId'] . '/messages';

		$body = array(
			'messaging_product' => 'whatsapp',
			'recipient_type'    => 'individual',
			'to'                => $to,
			'type'              => 'template',
			'template'          => array(
				'name'     => $template_name,
				'language' => array(
					'code' => $template_language,
				),
			),
		);

		// Add components if present
		if ( ! empty( $components ) ) {
			$body['template']['components'] = $components;
		}

		$args = array(
			'method'  => 'POST',
			'timeout' => 10,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->credentials['accessToken'],
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => array(
					'message' => $response->get_error_message(),
				),
			);
		}

		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );
		$status_code = wp_remote_retrieve_response_code( $response );

		// Check if there's an error in the API response
		if ( isset( $data['error'] ) ) {
			$data['success'] = false;

			// If Meta's API returns a 401, the stored access token is no longer
			// valid (revoked/expired). Flag it out-of-band so the admin sees the
			// "credentials invalid" notice and further sends are guarded.
			//
			// We must NOT write $this->credentials back here: it came from
			// Options::get_whatsapp_settings() with accessToken already decrypted,
			// so a raw update_option() would persist the token in plaintext and
			// corrupt accessTokenHash. set_whatsapp_access_token_invalid() records
			// the state without touching the encrypted settings struct.
			if ( 401 === $status_code ) {
				Options::set_whatsapp_access_token_invalid();
			}
		} elseif ( ! isset( $data['messages'][0]['id'] ) ) {
			// Check for the message ID if there was no error
			$data['success'] = false;
		} else {
			$data['success'] = true;
			// Save transient data containing timestamp for wp metrics whatsapp message tracking.
			$this->update_message_tracking_transient();
		}

		return $data;
	}
}

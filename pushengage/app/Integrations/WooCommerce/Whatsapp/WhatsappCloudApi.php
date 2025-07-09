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
					'message' => __( 'WhatsApp credentials is missing or invalid', 'pushengage' ),
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

			// if meta api returns status code 401, then check if the error is due to invalid access token
			// and update the store accessTokenHash in the credentials so that we can display the
			// access token mismatch notice to admin
			//
			// this is done because the access token is not valid anymore and we need to display the
			// access token mismatch notice to admin
			if ( 401 === $status_code ) {
				$this->credentials['accessTokenHash'] = $data['error']['message'];
				update_option( 'pushengage_whatsapp_settings', $this->credentials );
			}
		} elseif ( ! isset( $data['messages'][0]['id'] ) ) {
			// Check for the message ID if there was no error
			$data['success'] = false;
		} else {
			$data['success'] = true;
		}

		return $data;
	}
}

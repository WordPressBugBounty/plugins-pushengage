<?php
/**
 * WhatsApp status ability (registered only when WC + WhatsApp credentials are valid).
 *
 * @since 4.2.2
 */

namespace Pushengage\Integrations\Abilities;

use Pushengage\Integrations\Helpers;
use Pushengage\Integrations\WooCommerce\Whatsapp\WhatsappHelper;
use Pushengage\Utils\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WhatsappAbilities
 *
 * @since 4.2.2
 */
class WhatsappAbilities extends AbstractRegistrar {

	/**
	 * Register WhatsApp abilities when WooCommerce and WhatsApp credentials are valid.
	 *
	 * @since 4.2.2
	 * @return void
	 */
	public function register() {
		if ( ! Helpers::is_woocommerce_active() ) {
			return;
		}

		$whatsapp_settings = Options::get_whatsapp_settings();
		if ( ! WhatsappHelper::is_valid_whatsapp_credentials( $whatsapp_settings ) ) {
			return;
		}

		$this->register_ability(
			'pushengage/get-whatsapp-status',
			array(
				'label'            => __( 'Get WhatsApp Status', 'pushengage' ),
				'description'      => __( 'Returns whether WhatsApp integration credentials are valid.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_get_whatsapp_status' ),
				'annotations'      => array(
					'readonly'   => true,
					'idempotent' => true,
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'connected' => array( 'type' => 'boolean' ),
					),
				),
			)
		);
	}

	/**
	 * Execute get-whatsapp-status ability.
	 *
	 * @since 4.2.2
	 * @return array
	 */
	public function execute_get_whatsapp_status() {
		$whatsapp_settings = Options::get_whatsapp_settings();
		return array(
			'connected' => WhatsappHelper::is_valid_whatsapp_credentials( $whatsapp_settings ),
		);
	}
}

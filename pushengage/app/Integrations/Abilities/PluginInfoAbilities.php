<?php
/**
 * Plugin info and site environment abilities.
 *
 * @since 4.2.2
 */

namespace Pushengage\Integrations\Abilities;

use Pushengage\Utils\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PluginInfoAbilities
 *
 * @since 4.2.2
 */
class PluginInfoAbilities extends AbstractRegistrar {

	/**
	 * Register plugin-info and site-environment abilities.
	 *
	 * @since 4.2.2
	 * @return void
	 */
	public function register() {
		$this->register_ability(
			'pushengage/get-plugin-info',
			array(
				'label'            => __( 'Get Plugin Info', 'pushengage' ),
				'description'      => __( 'Returns plugin version, connection state, and key configuration. Use this when you need full plugin details; for a lightweight connection check, use Get Connection Status.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_get_plugin_info' ),
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
						'plugin_version'       => array( 'type' => 'string' ),
						'connected'            => array( 'type' => 'boolean' ),
						'site_id'              => array( 'type' => array( 'string', 'integer', 'null' ) ),
						'auto_push'            => array( 'type' => 'boolean' ),
						'notification_icon'    => array( 'type' => 'string' ),
						'featured_large_image' => array( 'type' => 'boolean' ),
						'multi_action_button'  => array( 'type' => 'boolean' ),
						'allowed_post_types'   => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
				),
			)
		);

		$this->register_ability(
			'pushengage/get-site-environment',
			array(
				'label'            => __( 'Get Site Environment', 'pushengage' ),
				'description'      => __( 'Returns WordPress, PHP, and server details for diagnostics.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_get_site_environment' ),
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
						'wordpress' => array(
							'type'       => 'object',
							'properties' => array(
								'version'      => array( 'type' => 'string' ),
								'locale'       => array( 'type' => 'string' ),
								'is_multisite' => array( 'type' => 'boolean' ),
								'home_url'     => array( 'type' => 'string' ),
								'site_url'     => array( 'type' => 'string' ),
								'timezone'     => array( 'type' => 'string' ),
								'debug_mode'   => array( 'type' => 'boolean' ),
							),
						),
						'php'       => array(
							'type'       => 'object',
							'properties' => array(
								'version'            => array( 'type' => 'string' ),
								'memory_limit'       => array( 'type' => 'string' ),
								'max_execution_time' => array( 'type' => 'string' ),
							),
						),
						'server'    => array(
							'type'       => 'object',
							'properties' => array(
								'software' => array( 'type' => 'string' ),
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Execute get-plugin-info ability.
	 *
	 * @since 4.2.2
	 * @return array
	 */
	public function execute_get_plugin_info() {
		$settings        = Options::get_site_settings();
		$has_credentials = Options::has_credentials();

		$post_types = Options::get_allowed_post_types_for_auto_push();

		return array(
			'plugin_version'       => defined( 'PUSHENGAGE_VERSION' ) ? PUSHENGAGE_VERSION : 'unknown',
			'connected'            => $has_credentials,
			'site_id'              => $has_credentials && ! empty( $settings['site_id'] ) ? $settings['site_id'] : null,
			'auto_push'            => ! empty( $settings['auto_push'] ),
			'notification_icon'    => ! empty( $settings['notification_icon_type'] ) ? $settings['notification_icon_type'] : 'featured_image',
			'featured_large_image' => ! empty( $settings['featured_large_image'] ),
			'multi_action_button'  => ! empty( $settings['multi_action_button'] ),
			'allowed_post_types'   => $post_types,
		);
	}

	/**
	 * Execute get-site-environment ability.
	 *
	 * @since 4.2.2
	 * @return array
	 */
	public function execute_get_site_environment() {
		return array(
			'wordpress' => array(
				'version'      => get_bloginfo( 'version' ),
				'locale'       => get_locale(),
				'is_multisite' => is_multisite(),
				'home_url'     => home_url(),
				'site_url'     => site_url(),
				'timezone'     => wp_timezone_string(),
				'debug_mode'   => defined( 'WP_DEBUG' ) && WP_DEBUG,
			),
			'php'       => array(
				'version'            => phpversion(),
				'memory_limit'       => ini_get( 'memory_limit' ),
				'max_execution_time' => ini_get( 'max_execution_time' ),
			),
			'server'    => array(
				'software' => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'unknown',
			),
		);
	}
}

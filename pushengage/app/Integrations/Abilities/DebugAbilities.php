<?php
/**
 * Debug log ability.
 *
 * @since 4.2.2
 */

namespace Pushengage\Integrations\Abilities;

use Pushengage\Logger;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DebugAbilities
 *
 * @since 4.2.2
 */
class DebugAbilities extends AbstractRegistrar {

	/**
	 * Register debug-log ability.
	 *
	 * @since 4.2.2
	 * @return void
	 */
	public function register() {
		$this->register_ability(
			'pushengage/get-debug-log',
			array(
				'label'            => __( 'Get Debug Log', 'pushengage' ),
				'description'      => __( 'Lists debug log files; reads a specific file when file_name is given. Admin-only — log contents may include URLs and request payloads.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_get_debug_log' ),
				'annotations'      => array(
					'readonly'   => true,
					'idempotent' => true,
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'file_name' => array(
							'type'        => 'string',
							'description' => __( 'Specific log file name to read. If omitted, lists all available log files.', 'pushengage' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'files'   => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'          => array( 'type' => 'string' ),
									'size_human'    => array( 'type' => 'string' ),
									'created_human' => array( 'type' => 'string' ),
								),
							),
						),
						'content' => array(
							'type'       => 'object',
							'properties' => array(
								'name'          => array( 'type' => 'string' ),
								'size'          => array( 'type' => 'integer' ),
								'size_human'    => array( 'type' => 'string' ),
								'created'       => array( 'type' => 'integer' ),
								'created_human' => array( 'type' => 'string' ),
								'content'       => array( 'type' => 'string' ),
								'url'           => array( 'type' => 'string' ),
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Execute get-debug-log ability.
	 *
	 * @since 4.2.2
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_get_debug_log( $input ) {
		try {
			$logger = Logger::get_instance();

			if ( ! empty( $input['file_name'] ) ) {
				$file_name = sanitize_text_field( $input['file_name'] );
				$content   = $logger->get_log_file_content( $file_name );

				if ( false === $content ) {
					return new \WP_Error( 'invalid-log-file', __( 'Log file not found or not readable.', 'pushengage' ) );
				}

				return array( 'content' => $content );
			}

			return array( 'files' => $logger->get_log_files() );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ability-error', $e->getMessage() );
		}
	}
}

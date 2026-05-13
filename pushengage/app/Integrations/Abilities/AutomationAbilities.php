<?php
/**
 * Push and WhatsApp automation campaign abilities (WooCommerce-gated).
 *
 * @since 4.2.2
 */

namespace Pushengage\Integrations\Abilities;

use Pushengage\Integrations\Helpers;
use Pushengage\Integrations\WooCommerce\NotificationSettings;
use Pushengage\Integrations\WooCommerce\NotificationTemplates;
use Pushengage\Utils\Options;
use Pushengage\Utils\StringUtils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AutomationAbilities
 *
 * Registers automation campaign abilities only when WooCommerce is active.
 *
 * @since 4.2.2
 */
class AutomationAbilities extends AbstractRegistrar {

	/**
	 * Register push and WhatsApp automation campaign abilities.
	 *
	 * @since 4.2.2
	 * @return void
	 */
	public function register() {
		if ( ! Helpers::is_woocommerce_active() ) {
			return;
		}

		$this->register_ability(
			'pushengage/list-push-automation-campaigns',
			array(
				'label'            => __( 'List Push Automation Campaigns', 'pushengage' ),
				'description'      => __( 'Lists WooCommerce push automation campaigns with admin and customer configs.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_list_push_automation_campaigns' ),
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
						'roles_options' => array(
							'type'                 => 'object',
							'description'          => __( 'Map of WordPress role keys to their display labels.', 'pushengage' ),
							'additionalProperties' => array( 'type' => 'string' ),
						),
						'campaigns'     => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'              => array( 'type' => 'string' ),
									'title'           => array( 'type' => 'string' ),
									'description'     => array( 'type' => 'string' ),
									'enabled'         => array( 'type' => 'boolean' ),
									'admin_config'    => array(
										'type'       => 'object',
										'properties' => array(
											'enabled' => array( 'type' => 'boolean' ),
											'roles'   => array(
												'type'  => 'array',
												'items' => array( 'type' => 'string' ),
											),
											'title'   => array( 'type' => 'string' ),
											'message' => array( 'type' => 'string' ),
											'url'     => array( 'type' => 'string' ),
										),
									),
									'customer_config' => array(
										'type'       => 'object',
										'properties' => array(
											'enabled' => array( 'type' => 'boolean' ),
											'title'   => array( 'type' => 'string' ),
											'message' => array( 'type' => 'string' ),
											'url'     => array( 'type' => 'string' ),
										),
									),
								),
							),
						),
					),
				),
			)
		);

		$this->register_ability(
			'pushengage/update-push-automation-campaign',
			array(
				'label'            => __( 'Update Push Automation Campaign', 'pushengage' ),
				'description'      => __( 'Enables, disables, or configures a WooCommerce push automation campaign.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_update_push_automation_campaign' ),
				'annotations'      => array(
					'destructive' => false,
					'idempotent'  => true,
				),
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'id'              => array(
							'type'        => 'string',
							'description' => __( 'Campaign event ID (e.g. new_order, cancelled_order).', 'pushengage' ),
						),
						'enabled'         => array(
							'type'        => 'boolean',
							'description' => __( 'Enable or disable the campaign.', 'pushengage' ),
						),
						'admin_config'    => array(
							'type'       => 'object',
							'properties' => array(
								'enabled' => array( 'type' => 'boolean' ),
								'roles'   => array(
									'type'  => 'array',
									'items' => array( 'type' => 'string' ),
								),
								'title'   => array(
									'type'      => 'string',
									'maxLength' => 170,
								),
								'message' => array(
									'type'      => 'string',
									'maxLength' => 256,
								),
								'url'     => array(
									'type'      => 'string',
									'maxLength' => 1600,
								),
							),
						),
						'customer_config' => array(
							'type'       => 'object',
							'properties' => array(
								'enabled' => array( 'type' => 'boolean' ),
								'title'   => array(
									'type'      => 'string',
									'maxLength' => 170,
								),
								'message' => array(
									'type'      => 'string',
									'maxLength' => 256,
								),
								'url'     => array(
									'type'      => 'string',
									'maxLength' => 1600,
								),
							),
						),
					),
					'required'   => array( 'id' ),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
			)
		);

		$this->register_ability(
			'pushengage/list-whatsapp-automation-campaigns',
			array(
				'label'            => __( 'List WhatsApp Automation Campaigns', 'pushengage' ),
				'description'      => __( 'Lists WhatsApp automation campaigns configured for WooCommerce events.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_list_whatsapp_automation_campaigns' ),
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
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'             => array( 'type' => 'string' ),
							'enabled'        => array( 'type' => 'boolean' ),
							'adminConfig'    => self::whatsapp_campaign_config_schema(),
							'customerConfig' => self::whatsapp_campaign_config_schema( true ),
						),
					),
				),
			)
		);
	}

	/**
	 * Output schema fragment for a WhatsApp campaign admin or customer config.
	 *
	 * @since 4.2.2
	 * @param bool $is_customer When true, returns the customer-config shape (recipientType,
	 *                          cartAbandonedCutoffTime); otherwise admin-config (recipients).
	 * @return array
	 */
	private static function whatsapp_campaign_config_schema( $is_customer = false ) {
		$variable_schema = array(
			'type'  => 'array',
			'items' => array(
				'type'       => 'object',
				'properties' => array(
					'key'   => array( 'type' => 'string' ),
					'value' => array( 'type' => 'string' ),
				),
			),
		);

		$properties = array(
			'enabled'          => array( 'type' => 'boolean' ),
			'templateName'     => array( 'type' => 'string' ),
			'templateLanguage' => array( 'type' => 'string' ),
			'headerVariables'  => $variable_schema,
			'bodyVariables'    => $variable_schema,
		);

		if ( $is_customer ) {
			$properties['recipientType']           = array( 'type' => 'string' );
			$properties['cartAbandonedCutoffTime'] = array(
				'type'        => 'integer',
				'minimum'     => 0,
				'description' => __( 'Cart abandonment cutoff in minutes (only for cart_abandoned campaigns).', 'pushengage' ),
			);
		} else {
			$properties['recipients'] = array( 'type' => 'string' );
		}

		return array(
			'type'       => 'object',
			'properties' => $properties,
		);
	}

	/**
	 * Execute list-push-automation-campaigns ability.
	 *
	 * @since 4.2.2
	 * @return array|\WP_Error
	 */
	public function execute_list_push_automation_campaigns() {
		try {
			$notifications = NotificationSettings::get_push_notification_events();
			$roles         = NotificationSettings::get_admin_roles();
			$row_settings  = get_option( 'pe_notifications_row_setting', array() );
			$campaigns     = array();

			foreach ( $notifications as $event_id => $event_data ) {
				$template_defaults = isset( NotificationTemplates::$templates[ $event_id ] )
					? NotificationTemplates::$templates[ $event_id ]
					: array();

				$settings = get_option( 'pe_notification_' . $event_id, array() );

				$enabled_row = isset( $row_settings[ 'enable_' . $event_id ] )
					? ( 'yes' === $row_settings[ 'enable_' . $event_id ] )
					: ( isset( $template_defaults['enable_row'] ) ? ( 'yes' === $template_defaults['enable_row'] ) : false );

				$admin_enabled    = isset( $settings['enable_admin'] ) ? $settings['enable_admin'] : ( isset( $template_defaults['enable_admin'] ) ? $template_defaults['enable_admin'] : 'no' );
				$customer_enabled = isset( $settings['enable_customer'] ) ? $settings['enable_customer'] : ( isset( $template_defaults['enable_customer'] ) ? $template_defaults['enable_customer'] : 'no' );

				$campaigns[] = array(
					'id'              => $event_id,
					'title'           => $event_data['title'],
					'description'     => $event_data['description'],
					'enabled'         => $enabled_row,
					'admin_config'    => array(
						'enabled' => ( 'yes' === $admin_enabled ),
						'roles'   => ( isset( $settings['admin_roles'] ) && is_array( $settings['admin_roles'] ) )
							? array_values( $settings['admin_roles'] )
							: array( 'administrator' ),
						'title'   => isset( $settings['admin_notification_title'] )
							? $settings['admin_notification_title']
							: ( isset( $template_defaults['admin_notification_title'] ) ? $template_defaults['admin_notification_title'] : '' ),
						'message' => isset( $settings['admin_notification_message'] )
							? $settings['admin_notification_message']
							: ( isset( $template_defaults['admin_notification_message'] ) ? $template_defaults['admin_notification_message'] : '' ),
						'url'     => isset( $settings['admin_notification_url'] )
							? $settings['admin_notification_url']
							: ( isset( $template_defaults['admin_notification_url'] ) ? $template_defaults['admin_notification_url'] : '' ),
					),
					'customer_config' => array(
						'enabled' => ( 'yes' === $customer_enabled ),
						'title'   => isset( $settings['notification_title'] )
							? $settings['notification_title']
							: ( isset( $template_defaults['notification_title'] ) ? $template_defaults['notification_title'] : '' ),
						'message' => isset( $settings['notification_message'] )
							? $settings['notification_message']
							: ( isset( $template_defaults['notification_message'] ) ? $template_defaults['notification_message'] : '' ),
						'url'     => isset( $settings['notification_url'] )
							? $settings['notification_url']
							: ( isset( $template_defaults['notification_url'] ) ? $template_defaults['notification_url'] : '' ),
					),
				);
			}

			return array(
				'roles_options' => $roles,
				'campaigns'     => $campaigns,
			);
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ability-error', $e->getMessage() );
		}
	}

	/**
	 * Execute update-push-automation-campaign ability.
	 *
	 * @since 4.2.2
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_update_push_automation_campaign( $input ) {
		try {
			$clean = self::sanitize_input( $input, array( 'id' => 'string' ) );

			$event_id = $clean['id'];
			$events   = NotificationSettings::get_push_notification_events();

			if ( ! array_key_exists( $event_id, $events ) ) {
				return new \WP_Error( 'invalid-campaign', __( 'Unknown campaign ID.', 'pushengage' ) );
			}

			if ( isset( $input['enabled'] ) ) {
				$row_settings                          = get_option( 'pe_notifications_row_setting', array() );
				$row_settings[ 'enable_' . $event_id ] = $input['enabled'] ? 'yes' : 'no';
				update_option( 'pe_notifications_row_setting', $row_settings );
			}

			$settings = get_option( 'pe_notification_' . $event_id, array() );

			if ( isset( $input['customer_config'] ) && is_array( $input['customer_config'] ) ) {
				$cc = $input['customer_config'];
				if ( isset( $cc['enabled'] ) ) {
					$settings['enable_customer'] = $cc['enabled'] ? 'yes' : 'no';
				}
				if ( isset( $cc['title'] ) ) {
					$settings['notification_title'] = sanitize_text_field( StringUtils::substr( $cc['title'], 0, 170 ) );
				}
				if ( isset( $cc['message'] ) ) {
					$settings['notification_message'] = sanitize_text_field( StringUtils::substr( $cc['message'], 0, 256 ) );
				}
				if ( isset( $cc['url'] ) ) {
					$settings['notification_url'] = sanitize_text_field( StringUtils::substr( $cc['url'], 0, 1600 ) );
				}
			}

			if ( isset( $input['admin_config'] ) && is_array( $input['admin_config'] ) ) {
				$ac = $input['admin_config'];
				if ( isset( $ac['enabled'] ) ) {
					$settings['enable_admin'] = $ac['enabled'] ? 'yes' : 'no';
				}
				if ( isset( $ac['roles'] ) && is_array( $ac['roles'] ) ) {
					$allowed_roles           = NotificationSettings::get_admin_roles();
					$settings['admin_roles'] = array_values(
						array_filter(
							array_map( 'sanitize_text_field', $ac['roles'] ),
							function ( $role ) use ( $allowed_roles ) {
								return array_key_exists( $role, $allowed_roles );
							}
						)
					);
				}
				if ( isset( $ac['title'] ) ) {
					$settings['admin_notification_title'] = sanitize_text_field( StringUtils::substr( $ac['title'], 0, 170 ) );
				}
				if ( isset( $ac['message'] ) ) {
					$settings['admin_notification_message'] = sanitize_text_field( StringUtils::substr( $ac['message'], 0, 256 ) );
				}
				if ( isset( $ac['url'] ) ) {
					$settings['admin_notification_url'] = sanitize_text_field( StringUtils::substr( $ac['url'], 0, 1600 ) );
				}
			}

			update_option( 'pe_notification_' . $event_id, $settings );

			return array( 'success' => true );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ability-error', $e->getMessage() );
		}
	}

	/**
	 * Execute list-whatsapp-automation-campaigns ability.
	 *
	 * @since 4.2.2
	 * @return array
	 */
	public function execute_list_whatsapp_automation_campaigns() {
		return array_values( Options::get_whatsapp_automation_campaigns() );
	}
}

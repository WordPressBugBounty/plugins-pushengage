<?php
/**
 * Auto-push settings, category, and attribute-mapping abilities.
 *
 * @since 4.2.2
 */

namespace Pushengage\Integrations\Abilities;

use Pushengage\Utils\Options;
use Pushengage\Utils\PublicPostTypes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SettingsAbilities
 *
 * @since 4.2.2
 */
class SettingsAbilities extends AbstractRegistrar {

	/**
	 * Register settings, category, and attribute-mapping abilities.
	 *
	 * @since 4.2.2
	 * @return void
	 */
	public function register() {
		$this->register_ability(
			'pushengage/get-auto-push-settings',
			array(
				'label'            => __( 'Get Auto Push Settings', 'pushengage' ),
				'description'      => __( 'Returns auto-push configuration: enabled post types, icon type, and feature flags.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_get_auto_push_settings' ),
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
						'auto_push'              => array( 'type' => 'boolean' ),
						'featured_large_image'   => array( 'type' => 'boolean' ),
						'multi_action_button'    => array( 'type' => 'boolean' ),
						'notification_icon_type' => array( 'type' => 'string' ),
						'allowed_post_types'     => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'available_post_types'   => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'label' => array( 'type' => 'string' ),
									'value' => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
			)
		);

		$this->register_ability(
			'pushengage/update-auto-push-settings',
			array(
				'label'            => __( 'Update Auto Push Settings', 'pushengage' ),
				'description'      => __( 'Updates auto-push: toggle, post types, icon, and feature flags.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_update_auto_push_settings' ),
				'annotations'      => array(
					'destructive' => false,
					'idempotent'  => true,
				),
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'auto_push'              => array(
							'type'        => 'boolean',
							'description' => __( 'Enable or disable auto-push for new posts.', 'pushengage' ),
						),
						'featured_large_image'   => array(
							'type'        => 'boolean',
							'description' => __( 'Use featured image as large notification image.', 'pushengage' ),
						),
						'multi_action_button'    => array(
							'type'        => 'boolean',
							'description' => __( 'Enable multi-action buttons on notifications.', 'pushengage' ),
						),
						'notification_icon_type' => array(
							'type'        => 'string',
							'enum'        => array( 'featured_image', 'site_image' ),
							'description' => __( 'Notification icon type.', 'pushengage' ),
						),
						'allowed_post_types'     => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'string' ),
							'description' => __( 'Post types that trigger auto-push.', 'pushengage' ),
						),
					),
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
			'pushengage/list-categories',
			array(
				'label'            => __( 'List Categories', 'pushengage' ),
				'description'      => __( 'Lists WordPress post categories, plus WooCommerce product categories when WooCommerce is active.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_list_categories' ),
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
							'name'     => array( 'type' => 'string' ),
							'taxonomy' => array(
								'type' => 'string',
								'enum' => array( 'category', 'product_cat' ),
							),
						),
					),
				),
			)
		);

		$this->register_ability(
			'pushengage/list-category-segment-mappings',
			array(
				'label'            => __( 'List Category Segment Mappings', 'pushengage' ),
				'description'      => __( 'Lists the mapping of WordPress categories to PushEngage segments.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_list_category_segment_mappings' ),
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
							'category_name'   => array( 'type' => 'string' ),
							'segment_id'      => array(
								'type'  => 'array',
								'items' => array( 'type' => 'integer' ),
							),
							'segment_name'    => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
							'segment_mapping' => array( 'type' => 'object' ),
						),
					),
				),
			)
		);

		$this->register_ability(
			'pushengage/get-attribute-mappings',
			array(
				'label'            => __( 'Get Attribute Mappings', 'pushengage' ),
				'description'      => __( 'Returns the mapping of PushEngage subscriber attributes to WordPress user meta keys.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_get_attribute_mappings' ),
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
					'type'                 => 'object',
					'description'          => __( 'Key-value pairs mapping PushEngage attribute keys to WordPress user meta keys.', 'pushengage' ),
					'additionalProperties' => array( 'type' => 'string' ),
				),
			)
		);
	}

	/**
	 * Execute get-auto-push-settings ability.
	 *
	 * @since 4.2.2
	 * @return array
	 */
	public function execute_get_auto_push_settings() {
		$settings          = Options::get_site_settings();
		$public_post_types = PublicPostTypes::get_all();

		$allowed_post_types = array();
		if ( isset( $settings['allowed_post_types'] ) ) {
			$decoded = json_decode( $settings['allowed_post_types'], true );

			// Legacy/staging data may contain a mixed array of strings and
			// {label, value} objects. The output schema declares `array<string>`,
			// so coerce every entry to a slug and drop anything we can't.
			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $item ) {
					if ( is_string( $item ) ) {
						$allowed_post_types[] = $item;
					} elseif ( is_array( $item ) && isset( $item['value'] ) && is_string( $item['value'] ) ) {
						$allowed_post_types[] = $item['value'];
					}
				}
			}
		}

		if ( empty( $allowed_post_types ) ) {
			$allowed_post_types = array_map(
				function ( $item ) {
					return $item['value'];
				},
				$public_post_types
			);
		}

		return array(
			'auto_push'              => ! empty( $settings['auto_push'] ),
			'featured_large_image'   => ! empty( $settings['featured_large_image'] ),
			'multi_action_button'    => ! empty( $settings['multi_action_button'] ),
			'notification_icon_type' => ! empty( $settings['notification_icon_type'] ) ? $settings['notification_icon_type'] : 'featured_image',
			'allowed_post_types'     => $allowed_post_types,
			'available_post_types'   => $public_post_types,
		);
	}

	/**
	 * Execute update-auto-push-settings ability.
	 *
	 * @since 4.2.2
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_update_auto_push_settings( $input ) {
		try {
			$settings = Options::get_site_settings();

			if ( isset( $input['auto_push'] ) ) {
				$settings['auto_push'] = (bool) $input['auto_push'];
			}

			if ( isset( $input['featured_large_image'] ) ) {
				$settings['featured_large_image'] = (bool) $input['featured_large_image'];
			}

			if ( isset( $input['multi_action_button'] ) ) {
				$settings['multi_action_button'] = (bool) $input['multi_action_button'];
			}

			if ( isset( $input['notification_icon_type'] ) ) {
				$settings['notification_icon_type'] = sanitize_text_field( $input['notification_icon_type'] );
			}

			if ( isset( $input['allowed_post_types'] ) && is_array( $input['allowed_post_types'] ) ) {
				$valid_post_types               = array_column( PublicPostTypes::get_all(), 'value' );
				$post_types                     = array_values(
					array_intersect(
						array_map( 'sanitize_text_field', $input['allowed_post_types'] ),
						$valid_post_types
					)
				);
				$settings['allowed_post_types'] = wp_json_encode( $post_types );
			}

			Options::update_site_settings( $settings );

			return array( 'success' => true );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ability-error', $e->getMessage() );
		}
	}

	/**
	 * Execute list-categories ability.
	 *
	 * @since 4.2.2
	 * @return array
	 */
	public function execute_list_categories() {
		$cats = array();

		foreach ( get_categories( array( 'hide_empty' => false ) ) as $category ) {
			$cats[] = array(
				'name'     => $category->name,
				'taxonomy' => 'category',
			);
		}

		if ( class_exists( 'WooCommerce' ) ) {
			$product_categories = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
				)
			);

			if ( is_array( $product_categories ) ) {
				foreach ( $product_categories as $product_category ) {
					$cats[] = array(
						'name'     => $product_category->name,
						'taxonomy' => 'product_cat',
					);
				}
			}
		}

		return $cats;
	}

	/**
	 * Execute list-category-segment-mappings ability.
	 *
	 * @since 4.2.2
	 * @return array
	 */
	public function execute_list_category_segment_mappings() {
		$settings = Options::get_site_settings();

		if ( ! empty( $settings['category_segmentation'] ) ) {
			$decoded = json_decode( $settings['category_segmentation'], true );
			if ( isset( $decoded['settings'] ) ) {
				return $decoded['settings'];
			}
		}

		return array();
	}

	/**
	 * Execute get-attribute-mappings ability.
	 *
	 * @since 4.2.2
	 * @return array
	 */
	public function execute_get_attribute_mappings() {
		$settings = Options::get_site_settings();

		if ( isset( $settings['attribute_user_meta_mapping'] ) && is_array( $settings['attribute_user_meta_mapping'] ) ) {
			return $settings['attribute_user_meta_mapping'];
		}

		return array();
	}
}

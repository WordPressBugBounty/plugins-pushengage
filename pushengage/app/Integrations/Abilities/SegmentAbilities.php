<?php
/**
 * Segment and audience-group abilities.
 *
 * @since 4.2.2
 */

namespace Pushengage\Integrations\Abilities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SegmentAbilities
 *
 * @since 4.2.2
 */
class SegmentAbilities extends AbstractRegistrar {

	/**
	 * Register segment and audience-group abilities.
	 *
	 * @since 4.2.2
	 * @return void
	 */
	public function register() {
		$this->register_ability(
			'pushengage/list-segments',
			array(
				'label'            => __( 'List Segments', 'pushengage' ),
				'description'      => __( 'List subscriber segments.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_list_segments' ),
				'annotations'      => array(
					'readonly'   => true,
					'idempotent' => true,
				),
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'limit'             => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => 100,
							'description' => __( 'Number of segments to return (1-100).', 'pushengage' ),
						),
						'page'              => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'Page number for pagination.', 'pushengage' ),
						),
						'segment_name_like' => array(
							'type'        => 'string',
							'description' => __( 'Search segments by name.', 'pushengage' ),
						),
						'expand'            => array(
							'type'        => 'string',
							'enum'        => array( 'subscriber_analytics' ),
							'description' => __( 'Expand additional data.', 'pushengage' ),
						),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'data' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'segment_id'               => array( 'type' => 'integer' ),
									'segment_name'             => array( 'type' => 'string' ),
									'segment_criteria'         => array( 'type' => 'object' ),
									'add_segment_on_page_load' => array( 'type' => 'integer' ),
									'status'                   => array( 'type' => 'integer' ),
									'subscribers'              => array( 'type' => 'integer' ),
								),
							),
						),
					),
				),
			)
		);

		$this->register_ability(
			'pushengage/list-audience-groups',
			array(
				'label'            => __( 'List Audience Groups', 'pushengage' ),
				'description'      => __( 'Lists audience groups available for targeting.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_list_audience_groups' ),
				'annotations'      => array(
					'readonly'   => true,
					'idempotent' => true,
				),
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'limit'     => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => 100,
							'description' => __( 'Number of groups to return (1-100).', 'pushengage' ),
						),
						'page'      => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'Page number for pagination.', 'pushengage' ),
						),
						'name_like' => array(
							'type'        => 'string',
							'description' => __( 'Search groups by name.', 'pushengage' ),
						),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'data' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'          => array( 'type' => 'integer' ),
									'name'        => array( 'type' => 'string' ),
									'description' => array( 'type' => 'string' ),
									'filter'      => array( 'type' => 'object' ),
									'status'      => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
			)
		);

		$this->register_ability(
			'pushengage/create-segment',
			array(
				'label'            => __( 'Create Segment', 'pushengage' ),
				'description'      => __( 'Create a new subscriber segment.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_create_segment' ),
				'annotations'      => array(
					'destructive' => false,
					'idempotent'  => false,
				),
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'segment_name'             => array(
							'type'        => 'string',
							'maxLength'   => 150,
							'description' => __( 'Name of the new segment (max 150 characters).', 'pushengage' ),
						),
						'add_segment_on_page_load' => array(
							'type'        => 'integer',
							'enum'        => array( 0, 1 ),
							'description' => __( 'Whether to add segment on page load (0 or 1, default 0).', 'pushengage' ),
						),
						'segment_criteria'         => array(
							'type'        => 'object',
							'description' => __( 'Criteria for segment membership with include/exclude rules.', 'pushengage' ),
							'properties'  => array(
								'include' => array(
									'type'  => 'array',
									'items' => self::segment_rule_item_schema(),
								),
								'exclude' => array(
									'type'  => 'array',
									'items' => self::segment_rule_item_schema(),
								),
							),
						),
					),
					'required'   => array( 'segment_name' ),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'segment_id'               => array( 'type' => 'integer' ),
						'segment_name'             => array( 'type' => 'string' ),
						'segment_criteria'         => array( 'type' => 'object' ),
						'add_segment_on_page_load' => array( 'type' => 'integer' ),
						'status'                   => array( 'type' => 'integer' ),
					),
				),
			)
		);

		$this->register_ability(
			'pushengage/add-subscribers-to-segment',
			array(
				'label'            => __( 'Add Subscribers to Segment', 'pushengage' ),
				'description'      => __( 'Add subscribers to an existing segment.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_add_subscribers_to_segment' ),
				'annotations'      => array(
					'destructive' => false,
					'idempotent'  => false,
				),
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'subscribers_id' => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'string' ),
							'description' => __( 'Array of subscriber IDs to add.', 'pushengage' ),
						),
						'segment_id'     => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'Target segment ID.', 'pushengage' ),
						),
					),
					'required'   => array( 'subscribers_id', 'segment_id' ),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'data' => array( 'type' => 'object' ),
					),
				),
			)
		);
	}

	/**
	 * Schema fragment for one segment-criteria rule (include/exclude items share this shape).
	 *
	 * @since 4.2.2
	 * @return array
	 */
	private static function segment_rule_item_schema() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'rule'  => array(
					'type' => 'string',
					'enum' => array( 'start', 'exact', 'contains' ),
				),
				'value' => array(
					'type'      => 'string',
					'maxLength' => 2000,
				),
			),
			'required'   => array( 'rule', 'value' ),
		);
	}

	/**
	 * Execute list-segments ability.
	 *
	 * @since 4.2.2
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_list_segments( $input ) {
		try {
			$clean = self::sanitize_input(
				$input,
				array(
					'limit'             => 'integer',
					'page'              => 'integer',
					'segment_name_like' => 'string',
					'expand'            => 'string',
				)
			);

			$response = pushengage()->get_segments( $clean );

			if ( is_wp_error( $response ) ) {
				return self::sanitize_error( $response );
			}

			return array( 'data' => self::unwrap_envelope( $response, 'rows' ) );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ability-error', $e->getMessage() );
		}
	}

	/**
	 * Execute list-audience-groups ability.
	 *
	 * @since 4.2.2
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_list_audience_groups( $input ) {
		try {
			$clean = self::sanitize_input(
				$input,
				array(
					'limit'     => 'integer',
					'page'      => 'integer',
					'name_like' => 'string',
				)
			);

			$response = pushengage()->get_audience_groups( $clean );

			if ( is_wp_error( $response ) ) {
				return self::sanitize_error( $response );
			}

			return array( 'data' => self::unwrap_envelope( $response, 'rows' ) );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ability-error', $e->getMessage() );
		}
	}

	/**
	 * Execute create-segment ability.
	 *
	 * @since 4.2.2
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_create_segment( $input ) {
		try {
			$clean = self::sanitize_input(
				$input,
				array(
					'segment_name'             => 'string',
					'add_segment_on_page_load' => 'integer',
					'segment_criteria'         => 'object',
				)
			);

			$response = pushengage()->create_segment( $clean );

			if ( is_wp_error( $response ) ) {
				return self::sanitize_error( $response );
			}

			return self::unwrap_envelope( $response );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ability-error', $e->getMessage() );
		}
	}

	/**
	 * Execute add-subscribers-to-segment ability.
	 *
	 * @since 4.2.2
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_add_subscribers_to_segment( $input ) {
		try {
			$clean = self::sanitize_input(
				$input,
				array(
					'subscribers_id' => 'array',
					'segment_id'     => 'integer',
				)
			);

			if ( empty( $clean['segment_id'] ) ) {
				return new \WP_Error( 'missing-param', __( 'A valid segment_id is required.', 'pushengage' ) );
			}

			if ( empty( $clean['subscribers_id'] ) ) {
				return new \WP_Error( 'missing-param', __( 'A non-empty subscribers_id array is required.', 'pushengage' ) );
			}

			$response = pushengage()->add_subscribers_to_segment(
				$clean['subscribers_id'],
				$clean['segment_id']
			);

			if ( is_wp_error( $response ) ) {
				return self::sanitize_error( $response );
			}

			return array( 'data' => self::unwrap_envelope( $response ) );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ability-error', $e->getMessage() );
		}
	}
}

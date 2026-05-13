<?php
/**
 * Analytics abilities (notifications summary, site overview, subscribers).
 *
 * @since 4.2.2
 */

namespace Pushengage\Integrations\Abilities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AnalyticsAbilities
 *
 * @since 4.2.2
 */
class AnalyticsAbilities extends AbstractRegistrar {

	/**
	 * Register analytics abilities.
	 *
	 * @since 4.2.2
	 * @return void
	 */
	public function register() {
		$this->register_ability(
			'pushengage/get-notifications-summary',
			array(
				'label'            => __( 'Get Notifications Summary', 'pushengage' ),
				'description'      => __( 'Returns aggregated send, view, and click counts across all notifications.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_get_notifications_summary' ),
				'annotations'      => array(
					'readonly'   => true,
					'idempotent' => true,
				),
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'include_meta' => array(
							'type'        => 'array',
							'items'       => array(
								'type' => 'string',
								'enum' => array( 'prev', 'curr', 'total' ),
							),
							'description' => __( 'Include additional metadata (prev, curr, total).', 'pushengage' ),
						),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'curr'          => self::count_value_object_schema(),
						'current30Days' => self::count_value_object_schema(),
						'prev'          => self::count_value_object_schema(),
						'prev30Days'    => self::count_value_object_schema(),
					),
				),
			)
		);

		$this->register_ability(
			'pushengage/get-analytics-overview',
			array(
				'label'            => __( 'Get Analytics Overview', 'pushengage' ),
				'description'      => __( 'Returns subscribers, impressions, and clicks over a date range, grouped by day, week, or month.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_get_analytics_overview' ),
				'annotations'      => array(
					'readonly'   => true,
					'idempotent' => true,
				),
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'start_created_at' => array(
							'type'        => 'string',
							'format'      => 'date',
							'description' => __( 'Start date (YYYY-MM-DD).', 'pushengage' ),
						),
						'end_created_at'   => array(
							'type'        => 'string',
							'format'      => 'date',
							'description' => __( 'End date (YYYY-MM-DD). Must be within one year of the start date.', 'pushengage' ),
						),
						'group_by'         => array(
							'type'        => 'string',
							'enum'        => array( 'day', 'week', 'month' ),
							'description' => __( 'Group results by time period (default: day).', 'pushengage' ),
						),
					),
					'required'   => array( 'start_created_at', 'end_created_at' ),
				),
				'output_schema'    => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'date_create'          => array( 'type' => 'string' ),
							'subscribers'          => array( 'type' => 'integer' ),
							'desktop_subscribers'  => array( 'type' => 'integer' ),
							'mobile_subscribers'   => array( 'type' => 'integer' ),
							'unsubscribed'         => array( 'type' => 'integer' ),
							'desktop_unsubscribed' => array( 'type' => 'integer' ),
							'mobile_unsubscribed'  => array( 'type' => 'integer' ),
							'notifications_sent'   => array( 'type' => 'integer' ),
							'total_notifications'  => array( 'type' => 'integer' ),
							'views'                => array( 'type' => 'integer' ),
							'click'                => array( 'type' => 'integer' ),
							'click_rate'           => array( 'type' => 'number' ),
						),
					),
				),
			)
		);

		$this->register_ability(
			'pushengage/get-subscriber-analytics',
			array(
				'label'            => __( 'Get Subscriber Analytics', 'pushengage' ),
				'description'      => __( 'Returns weekly active and total active subscribers.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_get_subscriber_analytics' ),
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
						'week'  => array(
							'type'        => 'integer',
							'description' => __( 'Active subscribers in the current week.', 'pushengage' ),
						),
						'total' => array(
							'type'        => 'integer',
							'description' => __( 'Total active subscribers.', 'pushengage' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Schema fragment for the {count, value} object used in notifications summary buckets.
	 *
	 * @since 4.2.2
	 * @return array
	 */
	private static function count_value_object_schema() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'count' => array( 'type' => 'number' ),
				'value' => array( 'type' => 'number' ),
			),
		);
	}

	/**
	 * Execute get-notifications-summary ability.
	 *
	 * @since 4.2.2
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_get_notifications_summary( $input ) {
		try {
			$clean = self::sanitize_input(
				$input,
				array(
					'include_meta' => 'array',
				)
			);
			return pushengage()->get_notification_analytics( $clean );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ability-error', $e->getMessage() );
		}
	}

	/**
	 * Execute get-analytics-overview ability.
	 *
	 * @since 4.2.2
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_get_analytics_overview( $input ) {
		try {
			$clean = self::sanitize_input(
				$input,
				array(
					'start_created_at' => 'date',
					'end_created_at'   => 'date',
					'group_by'         => 'string',
				)
			);

			if ( empty( $clean['start_created_at'] ) || empty( $clean['end_created_at'] ) ) {
				return new \WP_Error(
					'invalid-date',
					__( 'start_created_at and end_created_at must be valid dates in YYYY-MM-DD format.', 'pushengage' )
				);
			}

			$response = pushengage()->get_analytics_overview( $clean );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			// Schema declares the top-level output as a list. Upstream returns the
			// { status, data, user, ... } envelope, so unwrap `data`. Only accept a
			// genuine JSON list — an assoc-keyed `data` (e.g. when there are no
			// rows) collapses to an empty list rather than scalars.
			$raw_data = ( isset( $response['data'] ) && is_array( $response['data'] ) ) ? $response['data'] : array();
			$rows     = wp_is_numeric_array( $raw_data ) ? array_values( $raw_data ) : array();

			return array_values(
				array_filter(
					$rows,
					static function ( $row ) {
						return is_array( $row );
					}
				)
			);
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ability-error', $e->getMessage() );
		}
	}

	/**
	 * Execute get-subscriber-analytics ability.
	 *
	 * @since 4.2.2
	 * @return array|\WP_Error
	 */
	public function execute_get_subscriber_analytics() {
		try {
			return pushengage()->get_subscriber_analytics();
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ability-error', $e->getMessage() );
		}
	}
}

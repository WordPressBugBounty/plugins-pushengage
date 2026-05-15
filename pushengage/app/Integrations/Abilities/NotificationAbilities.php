<?php
/**
 * Connection-status and notification abilities.
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
 * Class NotificationAbilities
 *
 * @since 4.2.2
 */
class NotificationAbilities extends AbstractRegistrar {

	/**
	 * Register connection-status and notification abilities.
	 *
	 * @since 4.2.2
	 * @return void
	 */
	public function register() {
		$this->register_ability(
			'pushengage/get-connection-status',
			array(
				'label'            => __( 'Get Connection Status', 'pushengage' ),
				'description'      => __( 'Returns whether this site is connected to a PushEngage account.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_get_connection_status' ),
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
						'site_id'   => array( 'type' => array( 'string', 'integer', 'null' ) ),
					),
				),
			)
		);

		$this->register_ability(
			'pushengage/send-notification',
			array(
				'label'            => __( 'Send Notification', 'pushengage' ),
				'description'      => __( 'Send a push notification to subscribers.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_send_notification' ),
				'annotations'      => array(
					'destructive' => true,
					'idempotent'  => false,
				),
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'title'              => array(
							'type'        => 'string',
							'maxLength'   => 85,
							'description' => __( 'Notification title (max 85 characters).', 'pushengage' ),
						),
						'message'            => array(
							'type'        => 'string',
							'maxLength'   => 135,
							'description' => __( 'Notification message body (max 135 characters).', 'pushengage' ),
						),
						'notification_url'   => array(
							'type'        => 'string',
							'format'      => 'uri',
							'description' => __( 'URL to open when notification is clicked.', 'pushengage' ),
						),
						'notification_image' => array(
							'type'        => 'string',
							'format'      => 'uri',
							'pattern'     => '^https://',
							'description' => __( 'Optional HTTPS image URL for the notification.', 'pushengage' ),
						),
						'status'             => array(
							'type'        => 'string',
							'description' => __( 'Notification status: sent or draft.', 'pushengage' ),
							'enum'        => array( 'sent', 'draft' ),
						),
					),
					'required'   => array( 'title', 'message', 'notification_url' ),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'data' => array(
							'type'       => 'object',
							'properties' => array(
								'notification_id'      => array( 'type' => 'string' ),
								'notification_title'   => array( 'type' => 'string' ),
								'notification_message' => array( 'type' => 'string' ),
								'notification_url'     => array( 'type' => 'string' ),
								'status'               => array( 'type' => 'string' ),
								'created_at'           => array( 'type' => 'string' ),
							),
						),
					),
				),
			)
		);

		$this->register_ability(
			'pushengage/get-notification',
			array(
				'label'            => __( 'Get Notification', 'pushengage' ),
				'description'      => __( 'Returns details for a single notification.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_get_notification' ),
				'annotations'      => array(
					'readonly'   => true,
					'idempotent' => true,
				),
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'notification_id' => array(
							'type'        => 'integer',
							'description' => __( 'The notification ID.', 'pushengage' ),
						),
					),
					'required'   => array( 'notification_id' ),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'data' => array(
							'type'       => 'object',
							'properties' => array(
								'notification_id'      => array( 'type' => 'string' ),
								'notification_title'   => array( 'type' => 'string' ),
								'notification_message' => array( 'type' => 'string' ),
								'notification_url'     => array( 'type' => 'string' ),
								'notification_image'   => array( 'type' => 'string' ),
								'status'               => array( 'type' => 'string' ),
								'source'               => array( 'type' => 'string' ),
								'sentcount'            => array( 'type' => 'integer' ),
								'viewcount'            => array( 'type' => 'integer' ),
								'clickcount'           => array( 'type' => 'integer' ),
								'created_at'           => array( 'type' => 'string' ),
								'sent_at'              => array( 'type' => 'string' ),
							),
						),
					),
				),
			)
		);

		$this->register_ability(
			'pushengage/list-notifications',
			array(
				'label'            => __( 'List Notifications', 'pushengage' ),
				'description'      => __( 'Lists push notifications, with optional status filter and pagination.', 'pushengage' ),
				'execute_callback' => array( $this, 'execute_list_notifications' ),
				'annotations'      => array(
					'readonly'   => true,
					'idempotent' => true,
				),
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'status' => array(
							'type'        => 'string',
							'enum'        => array( 'sent', 'scheduled', 'draft' ),
							'description' => __( 'Filter by notification status.', 'pushengage' ),
						),
						'limit'  => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => 100,
							'description' => __( 'Number of notifications to return (1-100, default 10).', 'pushengage' ),
						),
						'page'   => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'Page number for pagination (default 1).', 'pushengage' ),
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
									'notification_id'      => array( 'type' => 'string' ),
									'notification_title'   => array( 'type' => 'string' ),
									'notification_message' => array( 'type' => 'string' ),
									'notification_url'     => array( 'type' => 'string' ),
									'status'               => array( 'type' => 'string' ),
									'sentcount'            => array( 'type' => 'integer' ),
									'viewcount'            => array( 'type' => 'integer' ),
									'clickcount'           => array( 'type' => 'integer' ),
									'created_at'           => array( 'type' => 'string' ),
								),
							),
						),
						'meta' => array(
							'type'       => 'object',
							'properties' => array(
								'page'  => array( 'type' => 'integer' ),
								'limit' => array( 'type' => 'integer' ),
								'count' => array( 'type' => 'integer' ),
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Execute get-connection-status ability.
	 *
	 * @since 4.2.2
	 * @return array|\WP_Error
	 */
	public function execute_get_connection_status() {
		try {
			$connected = pushengage()->is_site_connected();
			$site_id   = null;

			if ( $connected ) {
				$settings = Options::get_site_settings();
				$site_id  = isset( $settings['site_id'] ) ? $settings['site_id'] : null;
			}

			return array(
				'connected' => $connected,
				'site_id'   => $site_id,
			);
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ability-error', $e->getMessage() );
		}
	}

	/**
	 * Execute send-notification ability.
	 *
	 * @since 4.2.2
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_send_notification( $input ) {
		try {
			$clean = self::sanitize_input(
				$input,
				array(
					'title'              => 'string',
					'message'            => 'string',
					'notification_url'   => 'url',
					'notification_image' => 'url',
					'status'             => 'string',
				)
			);

			$params = array(
				'notification_title'   => $clean['title'],
				'notification_message' => $clean['message'],
				'notification_url'     => $clean['notification_url'],
			);

			if ( ! empty( $clean['notification_image'] ) ) {
				$params['notification_image'] = $clean['notification_image'];
			}

			if ( ! empty( $clean['status'] ) ) {
				$params['status'] = $clean['status'];
			}

			$response = pushengage()->send_notification( $params );

			if ( is_wp_error( $response ) ) {
				return self::sanitize_error( $response );
			}

			// Upstream `data` is a single notification record. notification_id
			// comes back as integer but the schema declares string — project to
			// schema fields with explicit casts.
			$data = self::unwrap_envelope( $response );

			return array(
				'data' => array(
					'notification_id'      => isset( $data['notification_id'] ) ? (string) $data['notification_id'] : '',
					'notification_title'   => isset( $data['notification_title'] ) ? (string) $data['notification_title'] : '',
					'notification_message' => isset( $data['notification_message'] ) ? (string) $data['notification_message'] : '',
					'notification_url'     => isset( $data['notification_url'] ) ? (string) $data['notification_url'] : '',
					'status'               => isset( $data['status'] ) ? (string) $data['status'] : '',
					'created_at'           => isset( $data['created_at'] ) ? (string) $data['created_at'] : '',
				),
			);
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ability-error', $e->getMessage() );
		}
	}

	/**
	 * Execute get-notification ability.
	 *
	 * @since 4.2.2
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_get_notification( $input ) {
		try {
			$clean = self::sanitize_input( $input, array( 'notification_id' => 'integer' ) );

			if ( empty( $clean['notification_id'] ) ) {
				return new \WP_Error( 'missing-param', __( 'A valid notification_id is required.', 'pushengage' ) );
			}

			$response = pushengage()->get_notification( $clean['notification_id'] );

			if ( is_wp_error( $response ) ) {
				return self::sanitize_error( $response );
			}

			// Same projection as send-notification, plus the extra fields the
			// get-notification schema declares (image, source, counts, sent_at).
			$data = self::unwrap_envelope( $response );

			return array(
				'data' => array(
					'notification_id'      => isset( $data['notification_id'] ) ? (string) $data['notification_id'] : '',
					'notification_title'   => isset( $data['notification_title'] ) ? (string) $data['notification_title'] : '',
					'notification_message' => isset( $data['notification_message'] ) ? (string) $data['notification_message'] : '',
					'notification_url'     => isset( $data['notification_url'] ) ? (string) $data['notification_url'] : '',
					'notification_image'   => isset( $data['notification_image'] ) ? (string) $data['notification_image'] : '',
					'status'               => isset( $data['status'] ) ? (string) $data['status'] : '',
					'source'               => isset( $data['source'] ) ? (string) $data['source'] : '',
					'sentcount'            => isset( $data['sentcount'] ) ? (int) $data['sentcount'] : 0,
					'viewcount'            => isset( $data['viewcount'] ) ? (int) $data['viewcount'] : 0,
					'clickcount'           => isset( $data['clickcount'] ) ? (int) $data['clickcount'] : 0,
					'created_at'           => isset( $data['created_at'] ) ? (string) $data['created_at'] : '',
					'sent_at'              => isset( $data['sent_at'] ) ? (string) $data['sent_at'] : '',
				),
			);
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ability-error', $e->getMessage() );
		}
	}

	/**
	 * Execute list-notifications ability.
	 *
	 * @since 4.2.2
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_list_notifications( $input ) {
		try {
			$clean = self::sanitize_input(
				$input,
				array(
					'status' => 'string',
					'limit'  => 'integer',
					'page'   => 'integer',
				)
			);

			$response = pushengage()->get_notifications( $clean );

			if ( is_wp_error( $response ) ) {
				return self::sanitize_error( $response );
			}

			// Upstream shape (confirmed against the live API):
			//   { status, data: { total, perPage, page, lastPage, data: [ ...rows ] },
			//     meta: { sent, draft, scheduled }, user }
			// Pagination metadata lives inside `data` (page/perPage/total); the
			// top-level `meta` is unrelated status counts and is not what our
			// schema describes. unwrap_envelope handles the outer step; the
			// inner row drill stays custom because rows are nested an extra level.
			$paginator = self::unwrap_envelope( $response );
			$raw_rows  = ( isset( $paginator['data'] ) && is_array( $paginator['data'] ) ) ? $paginator['data'] : array();
			$rows      = wp_is_numeric_array( $raw_rows ) ? array_values( $raw_rows ) : array();

			// Project each row to the declared schema fields and cast types.
			// notification_id comes back as integer from upstream but the schema
			// declares it string, so cast explicitly.
			$items = array();
			foreach ( $rows as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$items[] = array(
					'notification_id'      => isset( $row['notification_id'] ) ? (string) $row['notification_id'] : '',
					'notification_title'   => isset( $row['notification_title'] ) ? (string) $row['notification_title'] : '',
					'notification_message' => isset( $row['notification_message'] ) ? (string) $row['notification_message'] : '',
					'notification_url'     => isset( $row['notification_url'] ) ? (string) $row['notification_url'] : '',
					'status'               => isset( $row['status'] ) ? (string) $row['status'] : '',
					'sentcount'            => isset( $row['sentcount'] ) ? (int) $row['sentcount'] : 0,
					'viewcount'            => isset( $row['viewcount'] ) ? (int) $row['viewcount'] : 0,
					'clickcount'           => isset( $row['clickcount'] ) ? (int) $row['clickcount'] : 0,
					'created_at'           => isset( $row['created_at'] ) ? (string) $row['created_at'] : '',
				);
			}

			$meta = array(
				'page'  => isset( $paginator['page'] ) ? (int) $paginator['page'] : ( isset( $clean['page'] ) ? (int) $clean['page'] : 1 ),
				'limit' => isset( $paginator['perPage'] ) ? (int) $paginator['perPage'] : ( isset( $clean['limit'] ) ? (int) $clean['limit'] : 10 ),
				'count' => isset( $paginator['total'] ) ? (int) $paginator['total'] : count( $items ),
			);

			return array(
				'data' => $items,
				'meta' => $meta,
			);
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ability-error', $e->getMessage() );
		}
	}
}

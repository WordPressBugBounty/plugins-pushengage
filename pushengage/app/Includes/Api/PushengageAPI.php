<?php
namespace Pushengage\Includes\Api;

use Pushengage\Utils\Options;
use Pushengage\Includes\Api\HttpAPI;
use WP_Error;

/**
 * Class to act as an interface for addons to communicate
 * with PushEngage API.
 * @since 4.0.10
 */
class PushengageAPI {
	/**
	 * The single instance of the class.
	 *
	 * @since 4.0.10
	 */
	protected static $_instance = null;


	/**
	 * Plugin version.
	 *
	 * @since 4.0.10
	 *
	 * @var string
	 */
	public $version = '';


	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->version = PUSHENGAGE_VERSION;
	}


	/**
	 * Get the single instance of the class.
	 *
	 *@since 4.0.10
	 * @return PushEngageAPI
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 *  Checks if the plugin is connected to a pushengage site.
	 *
	 * @since 4.0.10
	 * @return boolean Returns true if site is connected else returns false
	 */
	public function is_site_connected() {
		return Options::has_credentials();
	}

	/**
	 * Send a push notification.
	 *
	 * @since 4.0.10
	 * @param array $params {
	 *      Array of request parameters.
	 *      @type string $notification_title    Required. Represents the title of the notification.
	 *      @type string $notification_message  Required. Represents the message of the notification.
	 *      @type string $notification_url      Required. Represents the URL to which the notification will redirect.
	 *      @type string $notification_image    Optional. Represents the URL of the image to be displayed in the notification.
	 *      @type string $big_image             Optional. URL of the big image to be displayed in the notification.
	 *      @type string $status                Defines the status of the notification, which can be 'sent', 'draft', 'scheduled'
	 *      @type array $utm_params Optional. {
	 *          @type string $utm_source
	 *          @type string $utm_medium
	 *          @type string $utm_campaign
	 *          @type string $utm_term
	 *          @type string $utm_content
	 *          @type bool   $enabled
	 *      }
	 *      @type array $notification_criteria  Optional. Criteria to define the target audience.
	 *      @type int   $require_interaction    Optional. Indicates if user interaction is required.
	 *
	 *      @type array $actions Optional. List of actions for the notification. {
	 *          @type array {
	 *              @type string $label
	 *              @type string $url
	 *          }
	 *      }
	 *
	 *      @type int   $expiry Optional. Expiry time in minutes.
	 *      @type array $android_push Optional. Android-specific push notification settings. {
	 *          @type string $deep_link
	 *          @type string $large_icon
	 *          @type string $big_picture
	 *          @type string $small_icon
	 *          @type string $group_key
	 *          @type int    $channel_id
	 *          @type array  $action_buttons {
	 *              @type array {
	 *                  @type string $label
	 *                  @type string $url
	 *              }
	 *          }
	 *      }
	 *      @type array $ios_push Optional. iOS-specific push notification settings. {
	 *          @type string $media
	 *          @type string $sound
	 *          @type int   $content_available
	 *          @type string $category
	 *          @type int $badge_increment
	 *          @type array $action_buttons {
	 *              @type array {
	 *                  @type string $id
	 *                  @type string $label
	 *              }
	 *          }
	 *      }
	 *      @type array $mobile_push Optional. Mobile-specific push notification settings. {
	 *          @type string $collapse_id
	 *          @type string $priority
	 *          @type array $additional_data {
	 *              @type array {
	 *                  @type string $key
	 *                  @type string $value
	 *              }
	 *          }
	 *      }
	 * }
	 * @return array|WP_Error Returns an array with the notification details on success, or WP_Error on failure.
	 *
	 * * @example
	 * Example of a successful return value:
	 * [
	 *      'status' => 200,
	 *      'data'   => [...], // Notification data
	 *      'user'   => [...]  // User data
	 * ]
	 */
	public function send_notification( $params ) {
		if ( ! is_array( $params ) || empty( $params ) ) {
			return new WP_Error( 'empty-params', __( 'Notification parameters are empty.', 'pushengage' ) );
		}

		$required_params = array(
			'notification_title'   => array(
				'message'    => __( 'Notification title is required.', 'pushengage' ),
			),
			'notification_message' => array(
				'message'    => __( 'Notification message is required.', 'pushengage' ),
			),
			'notification_url'     => array(
				'message'    => __( 'Notification URL is required.', 'pushengage' ),
			),
		);

		$error = new WP_Error();
		foreach ( $required_params as $key => $value ) {
			if ( empty( $params[ $key ] ) ) {
				$error->add( 'missing-params', $value['message'] );
			}
		}

		if ( $error->has_errors() ) {
			return $error;
		}

		if ( ! empty( $params['utm_params'] ) ) {
			$params['utm_params']['enabled'] = true;
		}

		if ( empty( $params['status'] ) ) {
			$params['status'] = 'sent';
		}

		$settings = Options::get_site_settings();
		$action   = 'draft' === $params['status'] ? 'draft' : 'sent';
		$path     = 'sites/' . $settings['site_id'] . '/notifications?action=' . $action;

		$options = array(
			'method' => 'POST',
			'body'   => $params,
		);

		return HttpAPI::send_private_api_request( $path, $options );
	}


	/**
	 * Get single notification by ID.
	 *
	 * @since 4.0.10
	 * @param number $id Notification ID.
	 * @return array|WP_Error
	 *
	 * @example
	 * Example of a successful return value:
	 * [
	 *      'status' => 200,
	 *      'data'   => [...], // Notification data
	 *      'user'   => [...]  // User data
	 * ]
	 */
	public function get_notification( $id ) {
		if ( ! $id ) {
			return new WP_Error( 'empty-notification-id', __( 'Notification ID is missing.', 'pushengage' ) );
		}
		$settings = Options::get_site_settings();
		$path = 'sites/' . $settings['site_id'] . '/notifications/' . $id;

		return HttpAPI::send_private_api_request( $path );
	}


	/**
	 * Get all the notifications.
	 *
	 * Retrieves notifications based on the specified filter criteria.
	 *
	 * @since 4.0.10
	 * @param array $params {
	 *    Optional. Array of request parameters to filter the notifications.
	 *
	 *    @type string  $status         Optional. Retrieves notifications based on their status. The possible values are:
	 *                                  - 'sent': Notifications that have been sent.
	 *                                  - 'scheduled': Notifications that are scheduled to be sent.
	 *                                  - 'draft': Notifications that are saved as drafts.
	 *
	 *    @type string  $start_sent_at  Optional. Retrieves notifications sent after the specified date.
	 *                                  The date must be in the format YYYY-MM-DD.
	 *
	 *    @type string  $end_sent_at    Optional. Retrieves notifications sent before the specified date.
	 *                                  The date must be in the format YYYY-MM-DD.
	 *
	 *    @type int     $limit          Optional. Specifies the maximum number of notifications to display in the results.
	 *                                  Default is 10. The maximum value is 100.
	 *
	 *    @type int     $page           Optional. Specifies the page number to display in the results. Default is 1.
	 *
	 *    @type string  $order_by_asc   Optional. Retrieves notifications in ascending order based on the specified
	 *                                  field. The possible values are:
	 *                                  'valid_from', 'sent_at', 'sentcount', 'viewcount', 'clickcount', 'failedcount',
	 *                                  'created_at', 'notification_id'
	 *
	 *    @type string  $order_by_desc  Optional. Retrieves notifications in descending order based on the specified
	 *                                  field. The possible values are:
	 *                                  'valid_from', 'sent_at', 'sentcount', 'viewcount', 'clickcount', 'failedcount',
	 *                                  'created_at', 'notification_id'
	 *
	 * }
	 *
	 * @return array|WP_Error The response data or WP_Error on failure.
	 *
	 *  * @example
	 * Example of a successful return value:
	 * [
	 *      'status' => 200,
	 *      'data'   => [...], // Notifications data
	 *      'meta'   => [...]  // Notifications metadata
	 *      'user'   => [...]  // User data
	 * ]
	 */
	public function get_notifications( $params = array() ) {
		$settings = Options::get_site_settings();
		$path     = 'sites/' . $settings['site_id'] . '/notifications';

		if ( ! empty( $params ) ) {
			$path .= '?' . http_build_query( $params );
		}

		return HttpAPI::send_private_api_request( $path );
	}


	/**
	 * Get all the segments.
	 *
	 * @since 4.0.10
	 * @param array $params {
	 *      Array of request parameters to filter the segments.
	 *      @type int       $limit Optional.    Specifies the maximum number of segments to display in the results.
	 *                                          The maximum value is 100. Default is 10.
	 *
	 *      @type int       $page Optional.     Specifies the page number to display in the results. Default is 1.
	 *
	 *      @type string    $segment_name_like  Optional. Retrieves segments that contain the specified string in their name.
	 *
	 *      @type string    $expand             Optional. comma separated list of extra data to be included with each
	 *                                          segment like: 'subscriber_analytics'
	 * }
	 * @return array|WP_Error The response data or WP_Error on failure.
	 *
	 * @example
	 * Example of a successful return value:
	 * [
	 *      'status' => 200,
	 *      'data'   => [...], // Segments data
	 *      'user'   => [...]  // User data
	 * ]
	 */
	public function get_segments( $params = array() ) {
		$settings = Options::get_site_settings();
		$path     = 'sites/' . $settings['site_id'] . '/segments';

		if ( ! empty( $params ) ) {
			$path .= '?' . http_build_query( $params );
		}

		return HttpAPI::send_private_api_request( $path );
	}

	/**
	 * Create a new segment.
	 *
	 * @since 4.0.10
	 *
	 * @param array $params {
	 *     Array of request parameters.
	 *
	 *     @type string $segment_name               Required. Represents the name of the segment.
	 *     @type int    $add_segment_on_page_load   Optional. Indicates whether to add the segment on page load. Default is 0.
	 *     @type array  $segment_criteria           Optional. Criteria to define the segment.
	 *
	 *     @type array $segment_criteria['include'] Optional. Array of inclusion rules. Each rule is an associative array
	 *                                              with the following keys:
	 *                                              @type string $rule  The rule type (e.g., 'start', 'exact', 'contains').
	 *                                              @type string $value The value to match against.
	 *
	 *     @type array $segment_criteria['exclude'] Optional. Array of exclusion rules. Each rule is an associative array
	 *                                              with the following keys:
	 *                                              @type string $rule  The rule type (e.g., 'start', 'exact', 'contains').
	 *                                              @type string $value The value to match against.
	 * }
	 * @return array|WP_Error Returns an array with the created segment details on success, or WP_Error on failure.
	 *
	 * @example
	 * Example of a successful return value:
	 * [
	 *      'status' => 200,
	 *      'data'   => [...], // Created segment data
	 *      'user'   => [...]  // User data
	 * ]
	 *
	 */
	public function create_segment( $params ) {
		$required_params = array(
			'segment_name' => __( 'Segment name is required.', 'pushengage' ),
		);

		$error = new WP_Error();
		foreach ( $required_params as $key => $message ) {
			if ( empty( $params[ $key ] ) ) {
				$error->add( 'invalid-request', $message );
			}
		}

		if ( $error->has_errors() ) {
			return $error;
		}

		$settings = Options::get_site_settings();
		$path = 'sites/' . $settings['site_id'] . '/segments';
		$options = array(
			'method' => 'POST',
			'body'   => $params,
		);

		return HttpAPI::send_private_api_request( $path, $options );
	}

	/**
	 * Add Subscribers to a Segment.
	 *
	 * @since 4.0.10
	 * @param array $subscribers_id Array of Subscriber id.
	 * @param int $segment_id Segment ID.
	 * @return array|WP_Error Returns an array on success, or WP_Error on failure.
	 */
	public function add_subscribers_to_segment( $subscribers_id, $segment_id ) {
		$error = new WP_Error();
		if ( ! is_array( $subscribers_id ) || empty( $subscribers_id ) ) {
			$error->add( 'invalid-request', __( 'Subscribers ID is required and must be a non-empty array.', 'pushengage' ) );
		}

		if ( ! $segment_id ) {
			$error->add( 'invalid-request', __( 'Segment ID is required.', 'pushengage' ) );
		}

		if ( $error->has_errors() ) {
			return $error;
		}

		$path = 'segments/addSegmentWithHash';
		$options = array(
			'method' => 'POST',
			'body'   => array(
				'hashes'        => $subscribers_id,
				'segment_id'    => $segment_id,
			),
		);

		return HttpAPI::send_rest_api_request( $path, $options );
	}

}


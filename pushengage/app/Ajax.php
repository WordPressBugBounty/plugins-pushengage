<?php
namespace Pushengage;

use Pushengage\HttpClient;
use Pushengage\ReviewNotice;
use Pushengage\Utils\Helpers;
use Pushengage\Utils\Options;
use Pushengage\Utils\ArrayHelper;
use Pushengage\Utils\NonceChecker;
use Pushengage\Utils\PublicPostTypes;
use Pushengage\Utils\RecommendedPlugins;
use Pushengage\Includes\Api\HttpAPI;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ajax {
	/**
	 * Admin ajax action prefix
	 *
	 * @since 4.0.0
	 *
	 * @var string
	 */
	private $action_prefix = 'wp_ajax_pe_';

	/**
	 * Admin ajax actions list
	 *
	 * @since 4.0.0
	 *
	 * @var array
	 */
	private $actions = array(
		'update_onboarding_data',
		'delete_onboarding_data',

		'update_onboarding_campaign_settings',
		'update_onboarding_retargeting_settings',
		'update_onboarding_recover_sales_settings',

		'get_all_plugins_info',
		'get_recommended_plugins_info',
		'install_recommended_plugins',

		'get_auto_push_settings',
		'update_auto_push_settings',

		'get_all_categories',
		'get_top_level_categories',
		'map_segment_with_categories',
		'get_category_segmentations',

		'get_post_metadata',

		'get_misc_settings',
		'update_misc_settings',

		'update_api_key',
		'get_help_docs',
		'verify_installation',

		'update_sw_error_settings',

		'get_woo_integration_settings',
		'update_woo_integration_settings',
		'delete_woo_integration_settings',
	);

	/**
	 * Constructor function to register hooks
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register all admin ajax hooks
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	private function register_hooks() {
		foreach ( $this->actions as $action ) {
			add_action( $this->action_prefix . $action, array( $this, $action ) );
		}
	}

	/**
	 * Check if the current user has the required capability.
	 *
	 * @since 4.0.8
	 *
	 * @param string $capability The capability to check.
	 *
	 * @return void
	 */
	private function check_capability( $capability ) {
		if ( empty( $capability ) || ! current_user_can( $capability ) ) {
			wp_send_json_error( __( 'Permission denied. Please make sure you have required permission to perform this action.', 'pushengage' ), 403 );
		}
	}

	/**
	 * Validate & update onboarding data into local database
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function update_onboarding_data() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		$payloads                   = array();
		$payloads['site_id']        = isset( $_POST['siteId'] ) ? filter_var( $_POST['siteId'], FILTER_SANITIZE_NUMBER_INT ) : null;
		$payloads['owner_id']       = isset( $_POST['ownerId'] ) ? filter_var( $_POST['ownerId'], FILTER_SANITIZE_NUMBER_INT ) : null;
		$payloads['api_key']        = isset( $_POST['apiKey'] ) ? sanitize_text_field( $_POST['apiKey'] ) : null;
		$payloads['site_key']       = isset( $_POST['siteKey'] ) ? sanitize_text_field( $_POST['siteKey'] ) : null;
		$payloads['site_subdomain'] = isset( $_POST['siteSubdomain'] ) ? sanitize_text_field( $_POST['siteSubdomain'] ) : null;

		// validating onboarding data
		$this->validate_onboarding_data( $payloads );

		$pushengage_settings             = Options::get_site_settings();
		$pushengage_settings['api_key']  = $payloads['api_key'];
		$pushengage_settings['site_id']  = intval( $payloads['site_id'] );
		$pushengage_settings['owner_id'] = intval( $payloads['owner_id'] );
		$pushengage_settings['site_key'] = $payloads['site_key'];
		$pushengage_settings['site_subdomain'] = $payloads['site_subdomain'];
		$pushengage_settings['setup_time'] = time();

		/**
		 * Reset 'service_worker_error' when site is connected.
		 *
		 * @since 4.0.6
		 *
		 */
		if ( isset( $pushengage_settings['service_worker_error'] ) ) {
			unset( $pushengage_settings['service_worker_error'] );
		}

		Options::update_site_settings( $pushengage_settings );

		wp_send_json_success( null, 200 );
	}

	/**
	 * Validate onboarding data
	 *
	 * @since 4.0.0
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	private function validate_onboarding_data( $data ) {
		$err_msg = __(
			'An error was encountered while connecting your account, please try again',
			'pushengage'
		);
		if (
				! $data['site_id'] ||
				! $data['api_key'] ||
				! $data['owner_id'] ||
				! $data['site_key'] ||
				! $data['site_subdomain']
			) {
			$error['message'] = $err_msg;
			$error['code']    = 'invalid_keys';
			wp_send_json_error( $error, 400 );
		}

		$site_info = HttpClient::get_site_info( $data['api_key'] );

		if (
				empty( $site_info ) ||
				ArrayHelper::get( $site_info, 'site.site_id' ) !== intval( $data['site_id'] ) ||
				ArrayHelper::get( $site_info, 'site.owner_id' ) !== intval( $data['owner_id'] ) ||
				ArrayHelper::get( $site_info, 'site.site_key' ) !== $data['site_key'] ||
				ArrayHelper::get( $site_info, 'site.site_subdomain' ) !== $data['site_subdomain']
			) {
			$error['message'] = $err_msg;
			$error['code']    = 'keys_mismatch';
			wp_send_json_error( $error, 400 );

		}

	}

	/**
	 * Get all plugins with status
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function get_all_plugins_info() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		$plugins                 = RecommendedPlugins::get_addons();
		$response['all_plugins'] = array_values( $plugins );
		wp_send_json_success( $response, 200 );
	}

	/**
	 * Get recommended plugins with statuses
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function get_recommended_plugins_info() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		$plugins                         = RecommendedPlugins::get_addons();
		$filtered_plugins                = array_filter(
			$plugins,
			function ( $k ) {
				$allowed = array( 'aioseo', 'optinmonster', 'monsterinsights', 'wpcode', 'wp-marketing-automations' );
				return in_array( $k, $allowed, true );
			},
			ARRAY_FILTER_USE_KEY
		);
		$response['recommended_plugins'] = array_values( $filtered_plugins );
		wp_send_json_success( $response, 200 );
	}

	/**
	 * Install recommended plugin
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function install_recommended_plugins() {
		NonceChecker::check();
		$this->check_capability( 'install_plugins' );

		$features = isset( $_POST['features'] ) ? json_decode( stripslashes_deep( $_POST['features'] ), true ) : array();
		if ( $features && count( $features ) > 0 ) {
			foreach ( $features as $feature ) {
				RecommendedPlugins::install( $feature['slug'] );
			}
		}
		wp_send_json_success( null, 200 );
	}

	/**
	 * Validate & update auto push data into wp local database
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function update_auto_push_settings() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		$pushengage_settings = Options::get_site_settings();

		if ( isset( $_POST['autoPush'] ) ) {
			$pushengage_settings['auto_push'] = filter_var( $_POST['autoPush'], FILTER_VALIDATE_BOOLEAN );
		}

		if ( isset( $_POST['featuredLargeImage'] ) ) {
			$pushengage_settings['featured_large_image'] = filter_var( $_POST['featuredLargeImage'], FILTER_VALIDATE_BOOLEAN );
		}

		if ( isset( $_POST['multiActionButton'] ) ) {
			$pushengage_settings['multi_action_button'] = filter_var( $_POST['multiActionButton'], FILTER_VALIDATE_BOOLEAN );
		}

		if ( isset( $_POST['notificationIconType'] ) ) {
			$pushengage_settings['notification_icon_type'] = sanitize_text_field( $_POST['notificationIconType'] );
		}

		$post_types = isset( $_POST['allowedPostTypes'] ) ? json_decode( stripslashes_deep( $_POST['allowedPostTypes'] ), true ) : array();
		array_walk(
			$post_types,
			function ( &$value ) {
				$value = sanitize_text_field( $value );
			}
		);

		$pushengage_settings['allowed_post_types'] = wp_json_encode( $post_types );

		Options::update_site_settings( $pushengage_settings );
		wp_send_json_success();

	}

	/**
	 * Validate & update WooCommerce integration data into wp local database
	 *
	 * @since 4.0.9
	 *
	 * @return void
	 */
	public function update_woo_integration_settings() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		$pushengage_settings = Options::get_site_settings();

		// Fields Schema to update settings.
		$fields = array(
			'cart_abandonment'   => array(
				'enable' => 'enableWooCartAbandonment',
				'id'     => 'cartAbandonmentTriggerId',
				'name'   => 'cartAbandonmentTriggerName',
			),
			'browse_abandonment' => array(
				'enable' => 'enableWooBrowseAbandonment',
				'id'     => 'browseAbandonmentTriggerId',
				'name'   => 'browseAbandonmentTriggerName',
			),
		);

		// Loop through each field and update settings.
		foreach ( $fields as $key => $field ) {
			if ( isset( $_POST[ $field['enable'] ] ) ) {
				$pushengage_settings['woo_integration'][ $key ]['enable'] = filter_var( $_POST[ $field['enable'] ], FILTER_VALIDATE_BOOLEAN );
			}

			if ( isset( $_POST[ $field['id'] ] ) ) {
				$pushengage_settings['woo_integration'][ $key ]['id'] = absint( $_POST[ $field['id'] ] );
			}

			if ( isset( $_POST[ $field['name'] ] ) ) {
				$pushengage_settings['woo_integration'][ $key ]['name'] = sanitize_text_field( $_POST[ $field['name'] ] );
			}
		}

		Options::update_site_settings( $pushengage_settings );
		wp_send_json_success();
	}

	/**
	 * Delete WooCommerce integration settings.
	 *
	 * @since 4.0.9
	 * @return void
	 */
	public function delete_woo_integration_settings() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		$pushengage_settings = Options::get_site_settings();
		$pushengage_settings['woo_integration'] = array();
		Options::update_site_settings( $pushengage_settings );
		wp_send_json_success();
	}

	/**
	 * Update api key to wp local database
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function update_api_key() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		$pushengage_settings = Options::get_site_settings();

		$pushengage_settings['api_key'] = isset( $_POST['apiKey'] )
			? sanitize_text_field( $_POST['apiKey'] )
			: ( isset( $pushengage_settings['api_key'] ) ? $pushengage_settings['api_key'] : '' );

		Options::update_site_settings( $pushengage_settings );
		wp_send_json_success();
	}

	/**
	 * Fetch auto push data from wp local database
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function get_auto_push_settings() {
		NonceChecker::check();
		$this->check_capability( 'edit_posts' );

		$public_post_types = PublicPostTypes::get_all();
		$pushengage_settings = Options::get_site_settings();
		$auto_push = ArrayHelper::only( $pushengage_settings, array( 'auto_push', 'featured_large_image', 'multi_action_button', 'notification_icon_type', 'allowed_post_types' ) );
		if ( isset( $auto_push['allowed_post_types'] ) ) {
			$auto_push['allowed_post_types'] = json_decode( $auto_push['allowed_post_types'] );
		} else {
			$auto_push['allowed_post_types'] = array_map(
				function( $item ) {
					return $item['value'];
				},
				$public_post_types
			);
		}

		wp_send_json_success(
			array(
				'autoPush'        => $auto_push,
				'publicPostTypes' => $public_post_types,
			),
			200
		);
	}

	/**
	 * Get WooCOmmerce integration settings
	 *
	 * @since 4.0.9
	 * @return void
	 */
	public function get_woo_integration_settings() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		$pushengage_settings = Options::get_site_settings();

		$woo_integration = ArrayHelper::only( $pushengage_settings, array( 'woo_integration' ) );

		wp_send_json_success(
			$woo_integration,
			200
		);
	}

	/**
	 * Delete onboarding data from wp local database
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function delete_onboarding_data() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		$pushengage_settings = Options::get_site_settings();
		if ( $pushengage_settings ) {
			$pushengage_settings['api_key']               = null;
			$pushengage_settings['site_id']               = null;
			$pushengage_settings['site_key']              = null;
			$pushengage_settings['owner_id']              = null;
			$pushengage_settings['category_segmentation'] = '';
			$pushengage_settings['setup_time'] = 0;
		}

		Options::update_site_settings( $pushengage_settings );
		ReviewNotice::delete_review_notice_settings();

		wp_send_json_success();
	}

	/**
	 * Get a list of all category names.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function get_all_categories() {
		NonceChecker::check();
		$this->check_capability( 'edit_posts' );

		$categories = get_categories();
		$cats       = array();
		foreach ( $categories as $category ) {
			$cats[] = $category->cat_name;
		}

		// If WooCommerce is active, get product categories and add it to array.
		if ( class_exists( 'WooCommerce' ) ) {
			$product_categories = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
				)
			);

			foreach ( $product_categories as $product_category ) {
				$cats[] = $product_category->name;
			}
		}

		wp_send_json_success( $cats );
	}

	/**
	 * Get all top level post categories.
	 *
	 * @since 4.1.0
	 *
	 * @return void
	 */
	public function get_top_level_categories() {
		NonceChecker::check();
		$this->check_capability( 'edit_posts' );

		$categories = get_categories(
			array(
				'parent' => 0,
			)
		);

		$cats = array();
		foreach ( $categories as $category ) {
			$cats[] = $category->cat_name;
		}

		wp_send_json_success( $cats );
	}

	/**
	 * Transforms WordPress category segments based on provided values and existing segments.
	 *
	 * @param array $segment The segment data, containing 'segment_id' and 'segment_name'.
	 * @param array $values An array of category names to include.
	 * @param array $category_segments An array of existing category segments.
	 *
	 * @return array The transformed category segments.
	 */
	public function transform_wp_category_segments( array $segment, array $values, array $category_segments ) {
		$payload = array();
		$segment_lists = $category_segments;

		$category_name_list = array_column( $category_segments, 'category_name' );

		foreach ( $values as $value ) {
			if ( ! in_array( $value, $category_name_list, true ) ) {  // Strict comparison for string values
				$segment_lists[] = array(
					'category_name'   => $value,
					'segment_id'      => array(),
					'segment_name'    => array(),
					'segment_mapping' => array(),
				);
			}
		}

		foreach ( $segment_lists as $category_segment ) {
			$segment_ids     = isset( $category_segment['segment_id'] ) ? $category_segment['segment_id'] : array();
			$segment_ids     = is_array( $segment_ids ) ? $segment_ids : array( $segment_ids );
			$segment_names   = isset( $category_segment['segment_name'] ) ? $category_segment['segment_name'] : array();
			$segment_names   = is_array( $segment_names ) ? $segment_names : array( $segment_names );
			$segment_mapping = isset( $category_segment['segment_mapping'] ) ? $category_segment['segment_mapping'] : array();

			if ( ! in_array( $category_segment['category_name'], $values, true ) ) { // Strict comparison
				$segment_ids = array_filter(
					$segment_ids,
					function ( $id ) use ( &$segment_mapping, $segment ) {
						if ( $id === $segment['segment_id'] ) {
							unset( $segment_mapping[ $id ] );
						}
						return $id !== $segment['segment_id'];
					}
				);

				$segment_names = array_filter(
					$segment_names,
					function ( $name ) use ( $segment ) {
						return $name !== $segment['segment_name'];
					}
				);

			} else {
				$segment_ids = array_unique( array_merge( $segment_ids, array( $segment['segment_id'] ) ) );
				$segment_names = array_unique( array_merge( $segment_names, array( $segment['segment_name'] ) ) );
				$segment_mapping[ $segment['segment_id'] ] = $segment['segment_name'];
			}

			if ( ! empty( $segment_ids ) && ! empty( $segment_names ) ) {
				$payload[] = array(
					'category_name'   => $category_segment['category_name'],
					'segment_id'      => array_values( $segment_ids ),
					'segment_name'    => array_values( $segment_names ),
					'segment_mapping' => $segment_mapping,
				);
			}
		}

		return $payload;
	}

	/**
	 * Map segment info for categories
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function map_segment_with_categories() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		$pushengage_settings = Options::get_site_settings();
		$settings            = isset( $_POST['settings'] ) ? json_decode( stripslashes_deep( $_POST['settings'] ), true ) : array();

		$pushengage_settings['category_segmentation'] = wp_json_encode( array( 'settings' => $settings ) );
		Options::update_site_settings( $pushengage_settings );

		wp_send_json_success(
			array(
				'settings' => $settings,
			)
		);
	}

	/**
	 * Get All Category Segmentations
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function get_category_segmentations() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		$pushengage_settings    = Options::get_site_settings();
		$category_segmentations = array();
		if ( $pushengage_settings && isset( $pushengage_settings['category_segmentation'] ) ) {
			$settings               = json_decode( $pushengage_settings['category_segmentation'], true );
			$category_segmentations = isset( $settings['settings'] ) ? $settings['settings'] : array();
		}

		wp_send_json_success( $category_segmentations );
	}

	/**
	 * Get pushengage meta data attached to a post
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function get_post_metadata() {
		NonceChecker::check();
		$this->check_capability( 'edit_posts' );

		$data    = array();
		$post_id = isset( $_POST['post_id'] ) ? absInt( $_POST['post_id'] ) : 0;
		$post    = $post_id ? get_post( $post_id ) : false;

		if ( ! $post_id || ! $post ) {
			wp_send_json_success( $data );
		}

		$push_options = Helpers::get_push_options_post_meta( $post_id );

		if ( ! empty( $push_options ) ) {
			$data = $push_options;

			if ( ! empty( $push_options['pe_wp_utm_params_enabled'] ) ) {
				$data['pe_wp_utm_params_enabled'] = true;
			}
			if ( ! empty( $push_options['pe_wp_audience_group_ids'] ) ) {
				$data['pe_wp_audience_group_ids'] = array_map( 'intval', $push_options['pe_wp_audience_group_ids'] );
			}

			$keys = array(
				'pe_wp_custom_title',
				'pe_wp_custom_message',
				'pe_wp_btn1_title',
				'pe_wp_btn2_title',
				'pe_wp_utm_source',
				'pe_wp_utm_medium',
				'pe_wp_utm_campaign',
				'pe_wp_utm_term',
				'pe_wp_utm_content',
			);

			// loop over the array and decode the html entities in value of these
			// keys to properly display them in the text field in UI
			foreach ( $keys as $key ) {
				$val = isset( $data[ $key ] ) ? Helpers::decode_entities( $data[ $key ] ) : '';
				if ( ! empty( $val ) ) {
					$data[ $key ] = $val;
				}
			}
		}

		$data['post_status'] = $post->post_status;
		wp_send_json_success( $data );
	}

	/**
	 * Get help docs json
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function get_help_docs() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		$options = array(
			'method'  => 'GET',
			'timeout' => 10,
		);

		$help_doc_url = 'https://assetscdn.pushengage.com/wp-plugin/help-docs.json';

		$wp_remote_request = wp_remote_request( $help_doc_url, $options );
		$body              = wp_remote_retrieve_body( $wp_remote_request );

		wp_send_json_success( json_decode( $body, true ) );
	}

	/**
	 * verify the PushEngage plugin installation
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function verify_installation() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		$data['active_caching_plugin'] = Helpers::get_active_caching_plugin();
		wp_send_json_success( $data );
	}

	/**
	 * Fetch pushengage_settings data to get misc
	 * settings from wp local database
	 *
	 * @since 4.0.5
	 *
	 * @return void
	 */
	public function get_misc_settings() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		$pushengage_settings = Options::get_site_settings();
		$misc_setting        = $pushengage_settings['misc'];

		wp_send_json_success( array( 'misc' => $misc_setting ) );
	}

	/**
	 * Update misc data inside pushengage_settings
	 *
	 * @since 4.0.5
	 *
	 * @return void
	 */
	public function update_misc_settings() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		$pushengage_settings = Options::get_site_settings();

		if ( isset( $_POST['hideAdminBarMenu'] ) ) {
			$pushengage_settings['misc']['hideAdminBarMenu'] = filter_var( $_POST['hideAdminBarMenu'], FILTER_VALIDATE_BOOLEAN );
		}

		if ( isset( $_POST['hideDashboardWidget'] ) ) {
			$pushengage_settings['misc']['hideDashboardWidget'] = filter_var( $_POST['hideDashboardWidget'], FILTER_VALIDATE_BOOLEAN );
		}

		Options::update_site_settings( $pushengage_settings );
		wp_send_json_success();
	}


	/**
	 * Update service worker error option inside pushengage_settings, 1 means show error and 0 means ignore error
	 *
	 * @since 4.0.6
	 *
	 * @return void
	 */
	public function update_sw_error_settings() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		if ( isset( $_POST['service_worker_error'] ) ) {
			$pushengage_settings = Options::get_site_settings();
			$pushengage_settings['service_worker_error'] = intval( $_POST['service_worker_error'] );
			Options::update_site_settings( $pushengage_settings );
		}

		wp_send_json_success();
	}

	/**
	 * Update onboarding campaign settings
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function update_onboarding_campaign_settings() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		if ( empty( $_POST['campaign_settings'] ) ) {
			wp_send_json_error( __( 'Invalid request. Campaign Settings data missing.', 'pushengage' ), 400 );
		}

		$campaign_settings = json_decode( stripslashes_deep( $_POST['campaign_settings'] ), true );
		$site_settings = Options::get_site_settings();

		$auto_push_post_types = ArrayHelper::get( $site_settings, 'allowed_post_types', PublicPostTypes::get_all() );

		if ( ! is_array( $auto_push_post_types ) ) {
			$auto_push_post_types = json_decode( $auto_push_post_types, true );
		}

		$order_updates_settings = get_option( 'pe_notifications_row_setting', array() );

		// For each item in the campaign settings, switch case statement based on id.
		foreach ( $campaign_settings as $key => $setting ) {
			switch ( $setting['id'] ) {
				case 'welcome_drip':
					$drip_id = ! empty( $setting['dripId'] ) ? $setting['dripId'] : false;
					$status  = $setting['enabled'] ? 'active' : 'cancelled';

					if ( $drip_id ) {
						$path = 'sites/' . $site_settings['site_id'] . '/automation/drips/' . $drip_id . '/status';

						$options = array(
							'method' => 'PATCH',
							'body'   => array(
								'status' => $status,
							),
						);

						$response = HttpAPI::send_private_api_request( $path, $options );

						if ( is_wp_error( $response ) ) {
							wp_send_json_error( $response->get_error_message(), 400 );
						}
					}
					break;
				case 'promote_new_posts':
					if ( $setting['enabled'] ) {
						$auto_push_post_types[] = 'post';
					} else {
						$auto_push_post_types = array_diff( $auto_push_post_types, array( 'post' ) );
					}
					break;
				case 'notify_new_product_listings':
					if ( $setting['enabled'] ) {
						$auto_push_post_types[] = 'product';
					} else {
						$auto_push_post_types = array_diff( $auto_push_post_types, array( 'product' ) );
					}
					break;
				case 'send_order_updates':
					$update_types = isset( $setting['subItems'] ) ? $setting['subItems'] : array();

					if ( ! empty( $update_types ) ) {
						foreach ( $update_types as $key => $update_type ) {
							$notification_settings = get_option( 'pe_notification_' . $update_type['id'], array() );

							if ( isset( $update_type['enable_admin'] ) ) {
								$notification_settings['enable_admin'] = $update_type['enable_admin'] ? 'yes' : 'no';
							}

							if ( isset( $update_type['enable_customer'] ) ) {
								$notification_settings['enable_customer'] = $update_type['enable_customer'] ? 'yes' : 'no';
							}

							if ( ! $setting['enabled'] ) {
								$order_updates_settings[ 'enable_' . $update_type['id'] ] = 'no';
							} else {
								$order_updates_settings[ 'enable_' . $update_type['id'] ] = $update_type['enabled'] ? 'yes' : 'no';
							}

							update_option( 'pe_notification_' . $update_type['id'], $notification_settings );
						}
					}
					break;
				case 'review_request':
						$order_updates_settings['enable_review_request'] = $setting['enabled'] ? 'yes' : 'no';
					break;
			}
			update_option( 'pe_notifications_row_setting', $order_updates_settings );

			$auto_push_post_types = array_unique( $auto_push_post_types );
			$site_settings['allowed_post_types'] = wp_json_encode( $auto_push_post_types );

			// Add default settings for auto push - enable autopush, featured image, multi action button.
			$site_settings['auto_push'] = true;
			$site_settings['featured_large_image'] = true;
			$site_settings['multi_action_button'] = true;

			Options::update_site_settings( $site_settings );
		}

		wp_send_json_success();
	}

	/**
	 * Update onboarding retargeting settings
	 *
	 * @since 4.1.0
	 *
	 * @return void
	 */
	public function update_onboarding_retargeting_settings() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		if ( empty( $_POST['segmentSettings'] ) ) {
			wp_send_json_error( __( 'Invalid request. Retargeting Settings data missing.', 'pushengage' ), 400 );
		}

		$segment_settings = json_decode( stripslashes_deep( $_POST['segmentSettings'] ), true );
		$site_settings    = Options::get_site_settings();
		$category_segmentations = ArrayHelper::get( $site_settings, 'category_segmentation', array() );
		$category_segmentation_settings = array();

		if ( ! empty( $category_segmentations ) ) {
			$category_segmentations = json_decode( $category_segmentations, true );
			$category_segmentation_settings = ArrayHelper::get( $category_segmentations, 'settings', array() );
		}

		// For each item in the retargeting settings, switch case statement based on id.
		foreach ( $segment_settings as $key => $setting ) {
			switch ( $setting['id'] ) {
				case 'primary_categories':
					if ( $setting['enabled'] ) {
						if ( ! empty( $setting['categories'] ) ) {
							foreach ( $setting['categories'] as $category ) {
								$segment = pushengage()->create_segment(
									array(
										'segment_name' => $category,
									)
								);

								if ( ! is_wp_error( $segment ) ) {
									$category_segmentation_settings = $this->transform_wp_category_segments(
										$segment['data'],
										array( $category ),
										$category_segmentation_settings
									);
									$site_settings['category_segmentation'] = wp_json_encode( array( 'settings' => $category_segmentation_settings ) );
								}
							}
						}
						Options::update_site_settings( $site_settings );
					}
					break;
				case 'customers':
					if ( $setting['enabled'] ) {
						$site_settings['enabled_customers_segment'] = true;
						pushengage()->create_segment(
							array(
								'segment_name' => 'Customers',
							)
						);
					}
					break;
				case 'leads':
					if ( $setting['enabled'] ) {
						$site_settings['enabled_leads_segment'] = true;
						pushengage()->create_segment(
							array(
								'segment_name' => 'Leads',
							)
						);
					}
					break;
			}
		}

		Options::update_site_settings( $site_settings );

		wp_send_json_success();
	}

	/**
	 * Update onboarding recover sales settings
	 *
	 * @since 4.1.0
	 *
	 * @return void
	 */
	public function update_onboarding_recover_sales_settings() {
		NonceChecker::check();
		$this->check_capability( 'manage_options' );

		if ( empty( $_POST['recoverSalesSettings'] ) ) {
			wp_send_json_error( __( 'Invalid request. Recover Sales Settings data missing.', 'pushengage' ), 400 );
		}

		$recover_sales_settings = json_decode( stripslashes_deep( $_POST['recoverSalesSettings'] ), true );
		$site_settings          = Options::get_site_settings();

		// For each item in the recover sales settings, switch case statement based on id.
		foreach ( $recover_sales_settings as $key => $setting ) {
			switch ( $setting['id'] ) {
				case 'cart_abandonment':
					if ( $setting['enabled'] ) {
						$site_settings['woo_integration']['cart_abandonment']['enable'] = true;
						$site_settings['woo_integration']['cart_abandonment']['name'] = $setting['triggerName'];
						$site_settings['woo_integration']['cart_abandonment']['id'] = $setting['triggerId'];
					} else {
						$site_settings['woo_integration']['cart_abandonment']['enable'] = false;
					}
					break;
				case 'browse_abandonment':
					if ( $setting['enabled'] ) {
						$site_settings['woo_integration']['browse_abandonment']['enable'] = true;
						$site_settings['woo_integration']['browse_abandonment']['name'] = $setting['triggerName'];
						$site_settings['woo_integration']['browse_abandonment']['id'] = $setting['triggerId'];
					} else {
						$site_settings['woo_integration']['browse_abandonment']['enable'] = false;
					}
					break;
			}
		}

		Options::update_site_settings( $site_settings );
		wp_send_json_success(
			array(
				'browse_enabled' => $site_settings['woo_integration']['browse_abandonment']['enable'],
				'cart_enabled'   => $site_settings['woo_integration']['cart_abandonment']['enable'],
			)
		);
	}
}

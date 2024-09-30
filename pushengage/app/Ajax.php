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

		'get_all_plugins_info',
		'get_recommended_plugins_info',
		'install_recommended_plugins',

		'get_auto_push_settings',
		'update_auto_push_settings',

		'get_all_categories',
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
}

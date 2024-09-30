<?php
namespace Pushengage;

use Pushengage\Utils\ArrayHelper;
use Pushengage\Utils\Helpers;
use Pushengage\Utils\Options;
use Pushengage\Integrations\Woo;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {
	/**
	 * Constructor function to register hooks
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		if ( is_admin() && current_user_can( 'manage_options' ) ) {
			add_action( 'admin_init', array( $this, 'pushengage_plugin_redirect' ), 9999 );
			add_action( 'admin_enqueue_scripts', array( $this, 'pushengage_hide_admin_notices' ) );
			add_action( 'admin_notices', array( $this, 'pushengage_display_admin_notices' ) );

			// WooCommerce integration hooks.
			Woo::init_hooks();
		}
	}

	/**
	 * Redirect to onboarding screen after activation
	 *
	 * @since 4.0.0
	 */
	public function pushengage_plugin_redirect() {
		if ( ! get_transient( 'pushengage_activation_redirect' ) ) {
			return;
		}

		delete_transient( 'pushengage_activation_redirect' );

		// Only do this for single site installs.
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		$url      = 'admin.php?page=pushengage';
		$settings = Options::get_site_settings();
		if ( ! isset( $settings['api_key'] ) || empty( $settings['api_key'] ) ) {
			$url .= '#/onboarding';
		}

		wp_safe_redirect( admin_url( $url ) );
		exit;
	}

	/**
	 * Remove all admin notices in pushengage plugin page
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function pushengage_hide_admin_notices() {
		$screen = get_current_screen();
		if ( 'toplevel_page_pushengage' === $screen->base ) {
			echo '<style>.update-nag, .updated, .error, .notice, .is-dismissible { display: none !important; }</style>';
		}
	}

	/**
	 * Display admin notices if site not connected
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function pushengage_display_admin_notices() {
		$screen = get_current_screen();
		// Added filter for site connection notice allowed screens.
		$allowed_screens = apply_filters(
			'pushengage_connection_notice_allowed_screens',
			array(
				'dashboard',
				'plugins',
			)
		);
		// Do not display admin notice on PushEngage Plugin page as we hide
		// the admin notice inside PushEngage Plugin
		if ( 'toplevel_page_pushengage' === $screen->base || ! in_array( $screen->id, $allowed_screens, true ) ) {
			return;
		}

		$settings = Options::get_site_settings();
		$api_key  = ArrayHelper::get( $settings, 'api_key', null );

		if ( ! $api_key ) {
			Pushengage::output_view( 'site-not-connected.php' );
		}

		if ( Options::has_credentials() ) {
			// 1 means show error and 0 means ignore sw error
			$service_worker_error = 0;
			if ( isset( $settings['service_worker_error'] ) ) {
				$service_worker_error = $settings['service_worker_error'];
			}

			$this->maybe_display_sw_error_notice( $settings['site_id'], $service_worker_error );
		}
	}

	/**
	 * Display service worker issue notice if not accessible publicly
	 *
	 * @since 4.0.5
	 *
	 * @param string $site_subdomain
	 *
	 * @return void
	 */
	public function maybe_display_sw_error_notice( $site_id, $service_worker_error ) {
		if ( ! Helpers::is_ssl() || empty( $service_worker_error ) ) {
			return;
		}

		$sw_url = '';

		$settings = HttpClient::get_site_settings( array( 'service_worker' ) );
		if ( ! empty( $settings['service_worker']['worker'] ) ) {
			$sw_path = $settings['service_worker']['worker'];

			// check if sw path is relative or absolute
			if ( false !== strpos( $sw_path, 'http' ) ) {
				$sw_url = $sw_path;
			} else {
				$origin_url = explode( wp_make_link_relative( PUSHENGAGE_PLUGIN_URL ), PUSHENGAGE_PLUGIN_URL );
				$sw_url = ! empty( $origin_url[0] ) ? $origin_url[0] . $sw_path : site_url( $sw_path, 'https' );
			}
		}

		if ( empty( $sw_url ) ) {
			return;
		}

		$data = array(
			'sw_url'  => $sw_url,
			'site_id' => $site_id,
		);

		Pushengage::output_view( 'service-worker-error.php', $data );
	}
}

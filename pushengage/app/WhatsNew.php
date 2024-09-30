<?php
namespace Pushengage;

use Pushengage\Utils\ArrayHelper;
use Pushengage\Utils\Options;
use Pushengage\Utils\NonceChecker;
use Pushengage\EnqueueAssets;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whats New banner class.
 *
 * @since 4.0.10
 */
class WhatsNew {
	/**
	 * Constructor.
	 *
	 * @since 4.0.10
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'init_ajax_hooks' ) );

		if ( self::is_showing() ) {
			add_action( 'admin_notices', array( __CLASS__, 'render' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );
		}
	}

	/**
	 * Init AJAX Hooks.
	 *
	 * @since 4.0.10
	 * @return void
	 */
	public function init_ajax_hooks() {
		add_action( 'wp_ajax_pe_dismiss_whats_new_notice', array( $this, 'dismiss_whats_new_notice' ) );
	}

	/**
	 * Is Showing the banner.
	 *
	 * @since 4.0.10
	 * @return boolean
	 */
	public static function is_showing() {
		global $pagenow;
		$pushengage_settings = Options::get_site_settings();
		$disabled  = ArrayHelper::get( $pushengage_settings, 'dismissed_whats_new_notice', false );

		return 'plugins.php' === $pagenow && ! $disabled;
	}

	/**
	 * Render the banner holder.
	 *
	 * @since 4.0.10
	 * @return void
	 */
	public static function render() {
		echo '<div id="pe-root" class="pushengage-app" data-app="whatsNew"></div>';
	}

	/**
	 * Load required scripts.
	 *
	 * @since 4.0.10
	 * @return void
	 */
	public static function load_scripts() {
		$screen = get_current_screen();
		if ( is_admin() && current_user_can( 'manage_options' ) && 'plugins' === $screen->base ) {
			EnqueueAssets::enqueue_pushengage_scripts();
			EnqueueAssets::localize_script();

			$pushengage_settings = Options::get_site_settings();

			wp_localize_script(
				'pushengage-main',
				'pushengageWhatsNew',
				array(
					'action'          => 'pe_dismiss_whats_new_notice',
					'nonce'           => NonceChecker::create_nonce(),
					'adminAjax'       => admin_url( 'admin-ajax.php' ),
					'dismissedNotice' => ArrayHelper::get( $pushengage_settings, 'dismissed_whats_new_notice', false ),
					'version'         => PUSHENGAGE_VERSION,
				)
			);
		}
	}

	/**
	 * Dismiss Whats New Notice
	 *
	 * @since 4.0.10
	 * @return void
	 */
	public function dismiss_whats_new_notice() {

		if ( ! check_ajax_referer( 'pushengage-nonce', '_wpnonce', false ) ) {
			$error['message'] = __( 'Invalid security token sent.', 'pushengage' );
			$error['code'] = 'invalid_security_token';
			wp_send_json_error( $error, 401 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied. Please make sure you have required permission to perform this action.', 'pushengage' ), 403 );
		}

		$pushengage_settings = Options::get_site_settings();
		// Setting version to current version to avoid showing whats new notice again for the same version.
		$pushengage_settings['version'] = PUSHENGAGE_VERSION;

		// Dismissed Whats New Notice.
		$pushengage_settings['dismissed_whats_new_notice'] = true;
		Options::update_site_settings( $pushengage_settings );
		wp_send_json_success();
	}

}

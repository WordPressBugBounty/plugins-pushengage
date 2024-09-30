<?php
namespace Pushengage;

use Pushengage\Ajax as AdminAjax;
use Pushengage\Core;
use Pushengage\NavMenu;
use Pushengage\EnqueueAssets;
use Pushengage\Integrations\Ajax as IntegrationsAjax;
use Pushengage\Includes\SubscriberSync;
use Pushengage\Utils\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Pushengage {

	/**
	 * Holds the class object.
	 *
	 * @since 4.0.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Admin object
	 *
	 * @since 4.0.0
	 *
	 * @var Admin
	 */
	public $admin;

	/**
	 * EnqueueAssets object
	 *
	 * @since 4.0.0
	 *
	 * @var EnqueueAssets
	 */
	public $enqueue_assets;

	/**
	 * NavMenu object
	 *
	 * @since 4.0.0
	 *
	 * @var NavMenu
	 */
	public $nav_menu;

	/**
	 * DashboardWidget object
	 *
	 * @since 4.0.5
	 *
	 * @var DashboardWidget
	 */
	public $dashboard_widget;

	/**
	 * AdminBarMenu object
	 *
	 * @since 4.0.5
	 *
	 * @var AdminBarMenu
	 */
	public $admin_bar_menu;

	/**
	 * Core object
	 *
	 * @since 4.0.0
	 *
	 * @var Core
	 */
	public $core;

	/**
	 * Ajax object
	 *
	 * @since 4.0.0
	 *
	 * @var Ajax
	 */
	public $ajax;

	/**
	 * Integrations ajax object
	 *
	 * @since 4.0.8
	 *
	 * @var IntegrationsAjax
	 */
	public $integrations_ajax;

	/**
	 * Review Notice object
	 *
	 * @since 4.0.5
	 *
	 * @var ReviewNotice
	 */
	public $review_notice;

	/**
	 * What's new screen
	 *
	 * @since 4.0.10
	 * @var WhatsNew
	 */
	public $whats_new;

	/**
	 * Initializes the Pushengage class
	 *
	 * @since 4.0.0
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Pushengage ) ) {
			self::$instance = new Pushengage();
			self::$instance->boot();
		}

		return self::$instance;
	}

	/**
	 * Bootstrap the plugin & Hook into actions and filters.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function boot() {
		// Load the plugin textdomain.
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		$this->init_hooks();
		$this->admin_ajax_hooks();
		$this->integrations_ajax_hooks();
	}

	/**
	 * Hooks methods to WP actions
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_action( 'init', array( $this, 'init_core_classes' ), 0 );
		register_activation_hook( PUSHENGAGE_FILE, array( '\Pushengage\Installer', 'plugin_install' ) );
		register_uninstall_hook( PUSHENGAGE_FILE, array( '\Pushengage\Uninstaller', 'plugin_uninstall' ) );
		add_action( 'admin_init', array( '\Pushengage\Upgrade', 'plugin_upgrade' ) );
	}

	/**
	* Load plugin text domain
	*
	* @since 4.0.0
	*
	* @return void
	*/
	public function load_plugin_textdomain() {
		$pe_locale = get_locale();
		if ( function_exists( 'get_user_locale' ) ) {
			$pe_locale = get_user_locale();
		}

		$pe_locale = apply_filters( 'plugin_locale', $pe_locale, 'pushengage' );
		$pe_mofile = sprintf( '%1$s-%2$s.mo', 'pushengage', $pe_locale );

		$pe_mofile1 = WP_LANG_DIR . '/pushengage/' . $pe_mofile;

		$pe_mofile2 = WP_LANG_DIR . '/plugins/pushengage/' . $pe_mofile;

		$pe_mofile3 = WP_LANG_DIR . '/plugins/' . $pe_mofile;

		$pe_mofile4 = dirname( plugin_basename( PUSHENGAGE_FILE ) ) . '/languages/';
		$pe_mofile4 = apply_filters( 'pushengage_languages_directory', $pe_mofile4 );

		if ( file_exists( $pe_mofile1 ) ) {
			load_textdomain( 'pushengage', $pe_mofile1 );
		} elseif ( file_exists( $pe_mofile2 ) ) {
			load_textdomain( 'pushengage', $pe_mofile2 );
		} elseif ( file_exists( $pe_mofile3 ) ) {
			load_textdomain( 'pushengage', $pe_mofile3 );
		} else {
			load_plugin_textdomain( 'pushengage', false, $pe_mofile4 );
		}
	}

	/**
	 * Include core classes
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function init_core_classes() {
		$this->admin            = new Admin();
		$this->enqueue_assets   = new EnqueueAssets();
		$this->nav_menu         = new NavMenu();
		$this->admin_bar_menu   = new AdminBarMenu();
		$this->core             = new Core();
		$this->dashboard_widget = new DashboardWidget();
		$this->review_notice    = new ReviewNotice();
		$this->whats_new        = new WhatsNew();

		// Load the frontend components
		if ( is_user_logged_in() && Options::has_credentials() ) {
			new SubscriberSync();
		}
	}

	/**
	 * Register admin ajax
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function admin_ajax_hooks() {
		$this->ajax = new AdminAjax();
	}

	/**
	 * Register integrations ajax
	 *
	 * @since 4.0.8
	 *
	 * @return void
	 */
	public function integrations_ajax_hooks() {
		$this->integrations_ajax = new IntegrationsAjax();
	}

	/**
	 * Get and include a view file for output.
	 *
	 * @since  4.0.5
	 *
	 * @param  string $filename The view file.
	 * @param  mixed  $data Arbitrary data to be made available to the view file.
	 *
	 * @return void
	 */
	public static function output_view( $filename, $data = array() ) {
		$view_file_path = PUSHENGAGE_VIEWS_PATH . DIRECTORY_SEPARATOR . basename( $filename );
		if ( ! file_exists( $view_file_path ) ) {
			return;
		}
		require $view_file_path;
	}
}

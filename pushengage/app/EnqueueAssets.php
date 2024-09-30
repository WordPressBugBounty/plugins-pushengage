<?php
namespace Pushengage;

use Pushengage\Utils\Helpers;
use Pushengage\Utils\NonceChecker;
use Pushengage\Utils\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EnqueueAssets {
	/**
	 * Holds the $version param value for enqueue scripts/styles
	 *
	 * @since 4.0.0
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Class constructor
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		$this->version = PUSHENGAGE_VERSION;
		$this->enqueue_admin_scripts();
	}

	/**
	 * Implement admin enqueue script hook
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();
		if ( is_admin() && current_user_can( 'manage_options' ) && 'toplevel_page_pushengage' === $screen->base ) {
			wp_enqueue_media();
			self::enqueue_pushengage_scripts();
			self::localize_script();

			// Trigger Filter to change PushEngage plugin footer
			add_filter( 'admin_footer_text', array( $this, 'replace_footer_text' ) );
		}
	}

	/**
	 * Enqueue pushengage style & script based on environment.
	 *
	 * @return void
	 */
	public static function enqueue_pushengage_scripts() {
		$self      = new static();
		$dep_array = array();
		global $wp_version;
		$is_version_more_than_five = version_compare( $wp_version, '5.0.0', '>=' );
		if ( $is_version_more_than_five ) {
			array_push( $dep_array, 'wp-i18n' );
		}

		wp_enqueue_style(
			'pushengage-font',
			PUSHENGAGE_PLUGIN_URL . 'assets/fonts/fonts.css',
			array(),
			$self->version
		);

		$assets_base_url = PUSHENGAGE_PLUGIN_URL . 'dist/';
		if ( defined( 'PUSHENGAGE_SCRIPT_URL' ) ) {
			$assets_base_url = PUSHENGAGE_SCRIPT_URL;
		}
		$assets = array(
			'static/js/vendor-core-js.js',
			'static/css/vendor-antd.css',
			'static/js/vendor-antd.js',
			'static/js/vendor-recharts.js',
			'static/js/vendor-common.js',
			'static/js/vendor-react.js',
			'static/js/vendor-moment-js.js',
			'static/js/vendor-emoji-picker.js',
			'static/css/main.css',
			'static/js/main.js',
		);

		foreach ( $assets as $asset ) {
			$filename  = basename( $asset );
			$extension = pathinfo( $filename, PATHINFO_EXTENSION );
			$handle    = 'pushengage-' . basename( $filename, '.' . $extension );
			if ( 'css' === $extension ) {
				wp_enqueue_style(
					$handle,
					$assets_base_url . $asset,
					array(),
					$self->version
				);
			}
			if ( 'js' === $extension ) {
				wp_enqueue_script(
					$handle,
					$assets_base_url . $asset,
					$dep_array,
					$self->version,
					true
				);
			}
		}

		if ( $is_version_more_than_five ) {
			wp_set_script_translations( 'pushengage-main', 'pushengage', plugin_dir_path( dirname( __FILE__ ) ) . 'languages' );
		}
	}

	/**
	 * Add block editor assets.
	 *
	 * @since 4.0.10
	 * @return void
	 */
	public static function enqueue_pre_publish_checklist_scripts() {
		$script_handle = 'prePostPublish';
		$assets_path   = 'assets/dist/';
		$script_data   = include_once plugin_dir_path( PUSHENGAGE_FILE ) . $assets_path . $script_handle . '.asset.php';

		wp_enqueue_script(
			'pushengage-' . $script_handle,
			PUSHENGAGE_PLUGIN_URL . $assets_path . $script_handle . '.js',
			$script_data['dependencies'],
			$script_data['version'],
			true
		);

		wp_enqueue_style(
			'pushengage-' . $script_handle,
			PUSHENGAGE_PLUGIN_URL . $assets_path . $script_handle . '.css',
			array(),
			$script_data['version']
		);
	}

	/**
	 * Localize pushengage variable to browser & window object
	 *
	 * @param [number] $post_id (Optional) ID of the post
	 * @param boolean  $is_create_edit_post_page (Optional) Check if on create or edit post page
	 * @param boolean  $is_on_dashboard_page (Optional) Check if on WordPress dashboard page
	 * @param boolean  $check_autosave_option (Optional) Check if autosave is enabled
	 *
	 * @return void
	 */
	public static function localize_script( $post_id = null, $is_create_edit_post_page = false ) {
		$current_user        = wp_get_current_user();
		$pushengage_settings = Options::get_site_settings();
		$pushengage          = array(
			'nonce'     => NonceChecker::create_nonce(),
			'adminAjax' => admin_url( 'admin-ajax.php' ),
			'siteName'  => get_bloginfo( 'name' ),
			'wpVersion' => get_bloginfo( 'version' ),
			'peVersion' => PUSHENGAGE_VERSION,
			'siteUrl'   => site_url(),
			'siteId'    => isset( $pushengage_settings['site_id'] ) ? $pushengage_settings['site_id'] : null,
			'ownerId'   => isset( $pushengage_settings['owner_id'] ) ? $pushengage_settings['owner_id'] : null,
			'apiKey'    => isset( $pushengage_settings['api_key'] ) ? $pushengage_settings['api_key'] : null,
			'siteKey'   => isset( $pushengage_settings['site_key'] ) ? $pushengage_settings['site_key'] : null,
			'authUser'  => array(
				'first_name' => $current_user->user_firstname,
				'last_name'  => $current_user->user_lastname,
				'email'      => $current_user->user_email,
			),
			'settings'  => $pushengage_settings,
			'assetsUrl' => PUSHENGAGE_PLUGIN_URL . 'assets/',
			'pluginUrl' => PUSHENGAGE_PLUGIN_URL,
			'pluginDashboardUrl' => esc_url( 'admin.php?page=pushengage#/' ),
			'apiBaseUrl'         => PUSHENGAGE_API_URL,
			'appDashboardUrl'    => PUSHENGAGE_APP_DASHBOARD_URL,
		);

		if ( $post_id ) {
			$pushengage['postId'] = $post_id;
		}

		if ( $is_create_edit_post_page ) {
			$pushengage['isCreateEditPostPage'] = $is_create_edit_post_page;

			$is_block_editor = Helpers::is_block_editor();
			if ( null !== $is_block_editor ) {
				$pushengage['isBlockEditor'] = $is_block_editor;
			}
		}

		// Check for integrations.
		$is_woo_active = is_plugin_active( 'woocommerce/woocommerce.php' ) && class_exists( 'woocommerce' );
		if ( $is_woo_active ) {
			$pushengage['wooCommerceActive'] = $is_woo_active;
		}
		$is_woocommerce_integrated = Helpers::is_woocommerce_integrated();
		if ( $is_woocommerce_integrated ) {
			$pushengage['isPEWooCommerceConnected'] = $is_woocommerce_integrated;
		}

		wp_localize_script( 'pushengage-main', 'pushengage', $pushengage );

		if ( ! $is_create_edit_post_page ) {
			wp_localize_script(
				'pushengage-main',
				'pushengageTranslations',
				array(
					'translations' => Helpers::get_jed_locale_data( 'pushengage' ),
				)
			);
		}
	}

	/**
	 * Add footer text to the WordPress admin screens.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function replace_footer_text() {
		$link_text = esc_html__( 'Give us a 5-star rating!', 'pushengage' );
		$href      = 'https://wordpress.org/support/plugin/pushengage/reviews/?filter=5#new-post';
		$link1     = sprintf(
			'<a href="%1$s" target="_blank" title="%2$s">&#9733;&#9733;&#9733;&#9733;&#9733;</a>',
			$href,
			$link_text
		);
		$link2     = sprintf(
			'<a href="%1$s" target="_blank" title="%2$s">WordPress.org</a>',
			$href,
			$link_text
		);

		printf(
			// Translators: 1 - The plugin name ("PushEngage"), - 2 - This placeholder will be replaced with star icons, - 3 - "WordPress.org" - 4 - The plugin name ("PushEngage").
			esc_html__(
				'Please rate %1$s %2$s on %3$s to help us spread the word. Thank you from the PushEngage team!',
				'pushengage'
			),
			sprintf( '<strong>%1$s</strong>', 'PushEngage' ),
			wp_kses_post( $link1 ),
			wp_kses_post( $link2 )
		);

		// Stop WP Core from outputting its version number and instead add both theirs & ours.
		global $wp_version;
		printf(
			wp_kses_post( '<p class="alignright">%1$s</p>' ),
			sprintf(
				// Translators: 1 - WordPress Version, - 2 - The plugin version
				esc_html__( 'WordPress %1$s | PushEngage %2$s', 'pushengage' ),
				esc_html( $wp_version ),
				esc_html( PUSHENGAGE_VERSION )
			)
		);

		remove_filter( 'update_footer', 'core_update_footer' );
	}
}

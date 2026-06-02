<?php
namespace Pushengage;

use Pushengage\Utils\Helpers;
use Pushengage\Utils\NonceChecker;
use Pushengage\Utils\Options;
use Pushengage\Utils\ArrayHelper;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EnqueueAssets {
	/**
	 * Class constructor
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function __construct() {
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
			PUSHENGAGE_VERSION
		);

		// Enqueue Font Awesome CSS for icon previews in admin React UI.
		// Using CDN ensures the latest icon set without bundling locally.
		wp_enqueue_style(
			'pushengage-font-awesome',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
			array(),
			'6.5.2'
		);

		$assets_base_url = PUSHENGAGE_PLUGIN_URL . 'dist/';
		if ( defined( 'PUSHENGAGE_SCRIPT_URL' ) ) {
			$assets_base_url = PUSHENGAGE_SCRIPT_URL;
		}
		$assets = array(
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
					PUSHENGAGE_VERSION
				);
			}
			if ( 'js' === $extension ) {
				wp_enqueue_script(
					$handle,
					$assets_base_url . $asset,
					$dep_array,
					PUSHENGAGE_VERSION,
					true
				);
			}
		}

		if ( $is_version_more_than_five ) {
			wp_set_script_translations( 'pushengage-main', 'pushengage', plugin_dir_path( __DIR__ ) . 'languages' );
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
	 * Build the payload fields shared by localize_script() and
	 * localize_script_for_post_editor(). Excludes credentials, the `settings`
	 * blob, and any context-specific keys — those are layered on by the caller.
	 *
	 * @since 4.2.5
	 *
	 * @return array
	 */
	private static function build_base_payload() {
		$current_user        = wp_get_current_user();
		$pushengage_settings = Options::get_site_settings();

		$payload = array(
			'nonce'              => NonceChecker::create_nonce(),
			'adminAjax'          => admin_url( 'admin-ajax.php' ),
			'siteName'           => get_bloginfo( 'name' ),
			'wpVersion'          => get_bloginfo( 'version' ),
			'peVersion'          => PUSHENGAGE_VERSION,
			'siteUrl'            => site_url(),
			'siteHost'           => wp_parse_url( get_site_url(), PHP_URL_HOST ),
			'wpDateFormat'       => get_option( 'date_format' ),
			'wpTimeFormat'       => get_option( 'time_format' ),
			'siteId'             => isset( $pushengage_settings['site_id'] ) ? $pushengage_settings['site_id'] : null,
			'ownerId'            => isset( $pushengage_settings['owner_id'] ) ? $pushengage_settings['owner_id'] : null,
			'authUser'           => array(
				'first_name' => $current_user->user_firstname,
				'last_name'  => $current_user->user_lastname,
				'email'      => $current_user->user_email,
			),
			'assetsUrl'          => PUSHENGAGE_PLUGIN_URL . 'assets/',
			'pluginUrl'          => PUSHENGAGE_PLUGIN_URL,
			'pluginDashboardUrl' => esc_url( 'admin.php?page=pushengage#/' ),
			'wpAdminUrl'         => admin_url( '/' ),
			'apiBaseUrl'         => PUSHENGAGE_API_URL,
			'appDashboardUrl'    => PUSHENGAGE_APP_DASHBOARD_URL,
		);

		$whatsapp_click_to_chat_settings = get_option( 'pushengage_whatsapp_click_to_chat', array() );
		$payload['isWhatsappClickToChatConfigured'] = ! empty( $whatsapp_click_to_chat_settings );

		$is_woo_active = is_plugin_active( 'woocommerce/woocommerce.php' ) && class_exists( 'woocommerce' );
		if ( $is_woo_active ) {
			$payload['wooCommerceActive']   = $is_woo_active;
			$payload['wooCommerceCurrency'] = get_woocommerce_currency();
		}
		if ( Helpers::is_woocommerce_integrated() ) {
			$payload['isPEWooCommerceConnected'] = true;
		}

		return $payload;
	}

	/**
	 * Localize the pushengage variable for the dashboard (credentialed context).
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function localize_script() {
		$pushengage_settings = Options::get_site_settings();
		$pushengage          = self::build_base_payload();

		$pushengage['apiKey']   = isset( $pushengage_settings['api_key'] ) ? $pushengage_settings['api_key'] : null;
		$pushengage['siteKey']  = isset( $pushengage_settings['site_key'] ) ? $pushengage_settings['site_key'] : null;
		$pushengage['settings'] = $pushengage_settings;

		wp_localize_script( 'pushengage-main', 'pushengage', $pushengage );

		wp_localize_script(
			'pushengage-main',
			'pushengageTranslations',
			array(
				'translations' => Helpers::get_jed_locale_data( 'pushengage' ),
			)
		);
	}

	/**
	 * Localize the pushengage variable for post-editor screens.
	 *
	 * Differs from localize_script() in three ways:
	 *   - apiKey and siteKey are NOT included.
	 *   - settings is a sanitized copy (no credential-bearing keys).
	 *   - proxyBaseUrl and restNonce are added so axios can route through the
	 *     WP REST proxy when apiKey is absent.
	 *
	 * @since 4.2.5
	 *
	 * @param int $post_id Post being edited.
	 * @return void
	 */
	public static function localize_script_for_post_editor( $post_id ) {
		$pushengage = self::build_base_payload();

		// apiKey intentionally omitted — proxy injects it server-side.
		// siteKey intentionally omitted — only needed for SDK init on dashboard.
		$pushengage['restNonce']            = wp_create_nonce( 'wp_rest' );
		$pushengage['proxyBaseUrl']         = esc_url_raw( rest_url( 'pushengage/v1/proxy/' ) );
		$pushengage['settings']             = Options::get_safe_site_settings();
		$pushengage['postId']               = $post_id;
		$pushengage['isCreateEditPostPage'] = true;

		$is_block_editor = Helpers::is_block_editor();
		if ( null !== $is_block_editor ) {
			$pushengage['isBlockEditor'] = $is_block_editor;
		}

		wp_localize_script( 'pushengage-main', 'pushengage', $pushengage );

		wp_localize_script(
			'pushengage-main',
			'pushengageTranslations',
			array(
				'translations' => Helpers::get_jed_locale_data( 'pushengage' ),
			)
		);
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
		$href      = 'https://wordpress.org/support/plugin/pushengage/reviews/#new-post';
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

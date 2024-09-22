<?php
/**
 * Description: Faux blocks for suggesting other Awesome Motive plugins.
 * Version:     1.1
 * Author:      Awesome Motive, Inc.
 * Author URI:  https://awesomemotive.com/
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Pushengage\Libraries\AMFB\Inc;
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';

use Plugin_Installer_Skin;
use Plugin_Upgrader;
use WP_Error;

/**
 * Faux Block class.
 */
class FauxBlock {

	/**
	 * List of the faux blocks.
	 */
	protected $faux_blocks;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Each plugin has to be added here. Keep aplhabetical order for easier reading.
		$this->faux_blocks = array(
			'aioseo'     =>
			array(
				'plugin' => 'all-in-one-seo-pack',
				'title' => 'Custom SEO',
				'keywords' => array( 'seo' ),
			),
			'duplicator'     =>
			array(
				'plugin' => 'duplicator',
				'title' => 'Custom Duplicator',
				'keywords' => array( 'duplicator' ),
			),
			'edd'     =>
			array(
				'plugin' => 'easy-digital-downloads',
				'title' => 'Custom Digital Store',
				'keywords' => array( 'digital store', 'payments', 'ecommerce' ),
			),
			'monster_insights'     =>
						array(
							'plugin' => 'google-analytics-for-wordpress',
							'title' => 'Google Analytics',
							'keywords' => array( 'google analytics' ),
						),
			'optin_monster'     =>
			array(
				'plugin' => 'optinmonster',
				'title' => 'Custom PopUp',
				'keywords' => array( 'popup' ),
			),
			'rafflepress'     =>
			array(
				'plugin' => 'rafflepress',
				'title' => 'Custom Giveaway',
				'keywords' => array( 'giveaway', 'contests' ),
			),
			'sb_instagram_feed'     =>
			array(
				'plugin' => 'instagram-feed',
				'title' => 'Custom Instagram Feed',
				'keywords' => array( 'instagram', 'social feed' ),
			),
			'sugar_calendar'     =>
			array(
				'plugin' => 'sugar-calendar',
				'title' => 'Custom Calendar',
				'keywords' => array( 'event', 'calendar', 'sugar calendar' ),
			),
			'wpcode'     =>
			array(
				'plugin' => 'insert-headers-and-footers',
				'title' => 'Custom code blocks',
				'keywords' => array( 'code', 'css', 'functions', 'snippet' ),
			),
			'wpforms' => array(
				'plugin' => 'wpforms',
				'title' => 'Custom Form',
				'keywords' => array( 'form' ),
			),
			'wpforms-survey' => array(
				'plugin' => 'wpforms',
				'title' => 'Custom Survey',
				'keywords' => array( 'survey' ),
			),
			'wp_mail_smtp'     =>
						array(
							'plugin' => 'wp-mail-smtp',
							'title' => 'Custom emails',
							'keywords' => array( 'mail', 'smtp' ),
						),
		);

	}

	/**
	 * Setup.
	 */
	public function setup() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'wp_ajax_am_faux_install', array( $this, 'install_plugin' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'install_scripts' ) );
		add_action( 'init', array( $this, 'optinmonster_no_redirect_on_activation' ) );
		add_action( 'init', array( $this, 'duplicator_no_redirect_on_activation' ) );
		add_action( 'init', array( $this, 'monsterinsights_no_redirect_on_activation' ) );
	}

	/**
	 * Register all the blocks we need. The title and the keywords are added from here.
	 */
	public function register_blocks() {
		$active_plugins = get_option( 'active_plugins' );

		$checks = array();
		foreach ( $active_plugins as $plugin ) {
			$check = ( strtok( $plugin, '/' ) );
			$check = str_replace( '-lite', '', $check );
			$checks[] = $check;
		}

		foreach ( $this->faux_blocks as $block => $info ) {

			if ( isset( $info['plugin'] ) && ! in_array( $info['plugin'], $checks ) ) {
				if ( ! empty( $info['title'] ) && ! empty( $info['keywords'] ) ) {
					register_block_type(
						__DIR__ . '/blocks/build/' . strtolower( $block ),
						array(
							'title'    => $info['title'],
							'keywords' => $info['keywords'],
						)
					);
				}
			}
		}
	}


	/**
	 * Enqueue the needed scripts.
	 */
	public function install_scripts() {
		$plugin_file = 'all_in_one_seo_pack.php';
		$url = admin_url( 'admin-ajax.php' );
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();
		wp_register_script( 'faux_js', false );
		wp_enqueue_script( 'faux_js' );

		wp_localize_script(
			'faux_js',
			'fauxData',
			array(
				'siteUrl'    => $url,
				'nonce'      => wp_create_nonce( 'am_faux_install' ),
				'pluginFile' => $plugin_file,
			)
		);

	}

	/**
	 * Install the plugin.
	 */
	public function install_plugin() {
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		if ( ! current_user_can( 'install_plugins' ) ) {
			$error = new WP_Error( 'no_permission', 'You do not have permission to install plugins.' );
			wp_send_json_error( $error );
		}

		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_REQUEST['nonce'] ), 'am_faux_install' ) ) {
			$error = new WP_Error( 'nonce_failure', 'The nonce was not valid.' );
			wp_send_json_error( $error );
		}

		if ( empty( $_REQUEST['plugin'] ) ) {
			$error = new WP_Error( 'missing_file', 'The plugin file was not specified.' );
			wp_send_json_error( $error );
		}
		$plugin_file = sanitize_text_field( $_REQUEST['plugin'] );
		$slug = strtok( $plugin_file, '/' );
		$plugin_dir = WP_PLUGIN_DIR . '/' . $slug;
		$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;

		if ( ! is_dir( $plugin_dir ) ) {
			$api = plugins_api(
				'plugin_information',
				array(
					'slug' => $slug,
					'fields' => array(
						'short_description' => false,
						'sections' => false,
						'requires' => false,
						'rating' => false,
						'ratings' => false,
						'downloaded' => false,
						'last_updated' => false,
						'added' => false,
						'tags' => false,
						'compatibility' => false,
						'homepage' => false,
						'donate_link' => false,
					),
				)
			);

			$skin = new Plugin_Installer_Skin( array( 'api' => $api ) );

			$upgrader = new Plugin_Upgrader( $skin );

			$install = $upgrader->install( $api->download_link );

			if ( true !== $install ) {
				$error = new WP_Error( 'failed_install', 'The plugin install failed.' );
				wp_send_json_error( $error );
			}
		}
		if ( file_exists( $plugin_path ) ) {
			activate_plugin( $plugin_path );
			wp_redirect( get_permalink() );
		} else {
			$error = new WP_Error( 'failed_activation', 'The plugin activation failed.' );
			wp_send_json_error( $error );
		}
		wp_die();
	}

	public function optinmonster_no_redirect_on_activation() {
		delete_transient( 'optin_monster_api_activation_redirect' );
	}

	public function duplicator_no_redirect_on_activation() {
		add_option( 'duplicator_redirect_to_welcome' );
	}

	public function monsterinsights_no_redirect_on_activation() {
		delete_transient( '_monsterinsights_activation_redirect' );
	}

}

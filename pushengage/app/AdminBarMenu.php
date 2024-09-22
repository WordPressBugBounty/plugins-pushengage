<?php

namespace Pushengage;

use Pushengage\Utils\Options;
use Pushengage\Utils\AdminNavMenuItems;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP Admin bar Menu
 *
 * @since 4.0.5
 */
class AdminBarMenu {



	/**
	 * Class constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		// Get options from settings
		$pushengage_settings = Options::get_site_settings();
		$hide_menu = $pushengage_settings['misc']['hideAdminBarMenu'];

		// Add the admin bar menu.
		if ( ! $hide_menu && current_user_can( 'manage_options' ) ) {
			if ( ! is_multisite() || ! is_network_admin() ) {
				add_action( 'admin_bar_menu', array( $this, 'render_admin_bar_menu' ), 1000 );
				if ( is_admin() ) {
					add_action( 'admin_head', array( $this, 'add_custom_styles' ) );
				} else {
					add_action( 'wp_head', array( $this, 'add_custom_styles' ) );
				}
			}
		}
	}

	/**
	 * Display admin bar menu.
	 *
	 * @param $wp_admin_bar
	 *
	 * @since 4.0.0
	 */
	public function render_admin_bar_menu( $wp_admin_bar ) {
		$settings = Options::get_site_settings();
		$api_key  = isset( $settings['api_key'] ) ? $settings['api_key'] : null;

		$title = __( 'PushEngage', 'pushengage' );
		$menu_icon = '<span class="pe-top-navbar-menu-icon"></span>';
		$label = '';
		if ( empty( $api_key ) ) {
			$label .= '<span class="wp-core-ui wp-ui-notification pe-admin-bar-menu-awaiting-mod">1</span>';
		}

		$args = array(
			'id' => 'pe-admin-bar-menu',
			'title' => $menu_icon . $title . $label,
			'href'  => admin_url( 'admin.php?page=pushengage#/' ),
		);

		$wp_admin_bar->add_node( $args );

		$sub_menu_list = AdminNavMenuItems::get_menu_items( 'wpadminbar' );

		foreach ( $sub_menu_list as $sub_menu ) {
			$args = array(
				'id' => 'pe-admin-bar-' . $sub_menu['id'],
				'title' => $sub_menu['label'],
				'href'  => admin_url( 'admin.php?page=pushengage#/' . $sub_menu['url'] ),
				'parent' => 'pe-admin-bar-menu',
			);
			$wp_admin_bar->add_node( $args );
		}

		// Show 'Upgrade To Pro sub menu at sub menu array position '9' if user in on free plan
		if ( AdminNavMenuItems::should_display_upgrade_submenu( $api_key ) ) {
			$upgrade_url = 'https://app.pushengage.com/account/billing?drawer=true' .
				'&utm_campaign=Plugin&utm_medium=AdminMenu&utm_source=WordPress&utm_content=UpgradeToPro&planType=business';
			$args = array(
				'id' => 'pe-admin-bar-upgrade',
				'title' => __( 'Upgrade to Pro', 'pushengage' ),
				'href'  => $upgrade_url,
				'parent' => 'pe-admin-bar-menu',
				'meta' => array(
					'class' => 'pe-admin-bar-upgrade-option',
					'target' => '_blank',
				),
			);
			$wp_admin_bar->add_node( $args );
		}
	}

	public function add_custom_styles() {
		Pushengage::output_view( 'wp-admin-bar-menu.php' );
	}
}

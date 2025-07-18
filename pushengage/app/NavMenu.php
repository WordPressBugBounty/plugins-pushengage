<?php

namespace Pushengage;

use Pushengage\Utils\Helpers;
use Pushengage\Utils\Options;
use Pushengage\Utils\AdminNavMenuItems;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NavMenu {


	/**
	 * Class constructor
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		$this->register_nav_menu();
	}

	/**
	 * Register admin navigation menu if user has right permission
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function register_nav_menu() {
		if ( is_admin() && current_user_can( 'manage_options' ) ) {
			add_action( 'admin_menu', array( $this, 'render_admin_menu' ), 60 );
			add_action( 'admin_footer', array( $this, 'add_upgrade_to_pro_custom_script' ) );
		}
	}

	/**
	 * Get the PushEngage Icon SVG, and maybe encode it.
	 *
	 * @since 4.0.0
	 *
	 * @param string $fill Color of the icon.
	 * @param bool   $return_encoded Whether the svg should be base_64 encoded.
	 *
	 * @return string Icon SVG.
	 */
	public function icon_svg( $fill = '#a0a5aa', $return_encoded = true ) {
		$icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 106 83" fill="' . $fill . '">
			<path d="M102 40.4611C101.856 54.0439 97.8267 64.8812 89.768 74.1291C88.3289 75.863 86.6021 77.4525 84.8752 78.8975C83.2922 80.3425 80.9897 80.3425 79.6946 78.8975C78.3994 77.308 78.6872 75.1405 80.4141 73.6956C84.0117 70.6611 86.8899 67.1932 89.1924 63.1472C92.6461 57.2228 94.6608 50.8649 94.9486 43.929C95.5242 31.9357 91.7827 21.6763 83.8678 12.862C82.7166 11.706 81.4214 10.55 80.2702 9.39403C78.6872 7.94905 78.3994 5.63709 79.6946 4.19211C80.9897 2.60263 83.1483 2.60263 84.8752 4.19211C94.0852 12.1395 99.5536 22.2543 101.568 34.2477C101.856 36.7041 101.856 39.1606 102 40.4611Z"/>
			<path d="M4 41.617C4.28781 26.5893 9.90015 14.018 21.2687 4.04761C22.8517 2.60263 25.1542 2.74713 26.3054 4.19211C27.6006 5.63709 27.3128 7.94905 25.7298 9.24953C17.9589 16.0409 13.0661 24.4218 11.4831 34.6811C9.32452 48.1194 12.9222 59.9683 21.9883 70.0831C23.1395 71.3836 24.5786 72.6841 25.8737 73.8401C27.4567 75.285 27.6006 77.4525 26.3054 78.8975C25.0103 80.3425 22.8517 80.3425 21.2687 79.042C11.1953 70.3721 5.43906 59.2458 4.28781 45.952C4.28781 45.2295 4.14391 44.507 4.14391 43.7845C4 43.062 4 42.3395 4 41.617Z"/>
			<path d="M17.9589 41.4725C18.2467 30.7797 22.1322 21.8208 30.3348 14.7404C32.0617 13.2955 33.9325 13.44 35.3715 15.0294C36.6667 16.4744 36.5228 18.3529 34.7959 19.9424C30.9104 23.5548 27.7445 27.7453 26.1615 32.9472C23.1395 43.4955 25.2981 52.8879 32.7812 60.9798C33.5007 61.8467 34.3642 62.5692 35.0837 63.4362C36.3789 64.7367 36.5228 66.7597 35.3715 68.0602C34.2203 69.5051 31.9178 69.7941 30.6226 68.4936C28.464 66.4707 26.4493 64.4477 24.5786 62.1357C20.6931 56.9338 18.5345 51.0094 18.1028 44.3625C18.1028 43.929 17.9589 43.4955 17.9589 42.9175C17.9589 42.484 17.9589 42.0505 17.9589 41.4725Z"/>
			<path d="M88.0411 41.617C87.7533 52.0209 83.8678 60.8353 76.0969 67.9157C74.9457 69.0716 73.5066 69.6496 71.9236 69.0716C69.6211 68.0602 69.0455 65.4592 70.7724 63.4362C72.7871 61.1243 75.0896 58.9568 76.8164 56.3558C83.7239 45.663 82.141 31.0687 73.0749 21.9653C72.3554 21.2429 71.6358 20.5204 70.9163 19.7979C69.6211 18.4974 69.4772 16.4744 70.6285 15.1739C71.9236 13.729 73.9383 13.44 75.5213 14.596C78.3994 16.9079 80.8458 19.6534 82.7166 22.8323C85.7386 27.7453 87.6094 33.0917 87.8972 38.8716C87.8972 39.7386 87.8972 40.6056 88.0411 41.617Z"/>
			<path d="M73.7944 40.7501C73.7944 47.975 71.348 53.0324 66.743 57.3673C65.1601 58.9568 62.8576 58.9568 61.5624 57.3673C60.2673 55.9223 60.2673 53.7549 61.9941 52.3099C64.8722 49.5644 66.743 46.241 66.8869 42.195C67.0308 38.2936 65.8796 34.9701 63.2893 32.0802C62.8576 31.5022 62.2819 31.0687 61.8502 30.6352C60.4112 29.1902 60.2673 27.1673 61.5624 25.7223C62.8576 24.2773 65.0162 24.1328 66.5991 25.5778C69.0455 27.7453 70.9163 30.3462 72.2115 33.3807C73.3627 35.9816 73.9383 38.7271 73.7944 40.7501Z"/>
			<path d="M41.2717 24.5663C43.2863 24.5663 44.2937 25.2888 45.0132 26.7338C45.7327 28.1787 45.301 29.6237 44.1498 30.6352C42.279 32.5137 40.696 34.3922 39.8326 36.9931C38.1057 42.484 39.257 47.2525 42.9985 51.4429C43.2863 51.8764 43.7181 52.1654 44.1498 52.5989C45.5888 54.1884 45.7327 56.2113 44.2937 57.6563C42.9985 58.9568 40.8399 59.1013 39.4009 57.8008C35.8032 54.6219 33.3568 50.8649 32.6373 46.0965C31.1982 38.2936 33.3568 31.5022 39.1131 26.1558C39.8326 25.1443 40.8399 24.8553 41.2717 24.5663Z"/>
			<path d="M52.7841 48.4084C48.8987 48.4084 45.8767 45.374 45.8767 41.4725C45.8767 37.5711 49.0426 34.5367 52.928 34.5367C56.8135 34.5367 59.8355 37.5711 59.8355 41.4725C59.8355 45.5185 56.8135 48.4084 52.7841 48.4084Z"/>
			</svg>';

		if ( $return_encoded ) {
			$icon = 'data:image/svg+xml;base64,' . base64_encode( $icon ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}

		return $icon;
	}

	/**
	 * Render admin menu
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function render_admin_menu() {
		$sub_menu_list = AdminNavMenuItems::get_menu_items();

		$settings = Options::get_site_settings();
		$label    = esc_html__( 'PushEngage', 'pushengage' );
		$api_key  = isset( $settings['api_key'] ) ? $settings['api_key'] : null;

		if ( empty( $api_key ) ) {
			$label .= ' <span class="awaiting-mod pe-nav-menu-awaiting-mod">1</span>';
		}
		add_menu_page(
			esc_html__( 'PushEngage', 'pushengage' ),
			$label,
			'manage_options',
			'pushengage',
			array( $this, 'render_admin_menu_view' ),
			$this->icon_svg(),
			apply_filters( 'pushengage_menu_position', '58.9' )
		);

		foreach ( $sub_menu_list as $sub_menu ) {
			add_submenu_page(
				'pushengage',
				$sub_menu['label'],
				$sub_menu['label'],
				'manage_options',
				'pushengage#/' . $sub_menu['url'],
				array( $this, 'render_admin_menu_view' )
			);
		}

		$woo_push_notifications_page = Helpers::is_woocommerce_integrated() ? 'pushengage#/campaigns/triggers' : 'pushengage#/settings/integrations';

		add_submenu_page(
			'woocommerce',
			esc_html__( 'Push Notifications', 'pushengage' ),
			esc_html__( 'Push Notifications', 'pushengage' ),
			'manage_options',
			$woo_push_notifications_page,
			array( $this, 'render_admin_menu_view' )
		);

		global $submenu;

		// Show 'Upgrade To Pro sub menu at sub menu array position '9' if user in on free plan
		if ( AdminNavMenuItems::should_display_upgrade_submenu( $api_key ) ) {
			$upgrade_url = 'https://app.pushengage.com/account/billing?drawer=true' .
				'&utm_campaign=Plugin&utm_medium=AdminMenu&utm_source=WordPress&utm_content=UpgradeToPro&planType=business';

			add_submenu_page(
				'pushengage',
				esc_html__( 'Upgrade to Pro', 'pushengage' ),
				esc_html__( 'Upgrade to Pro', 'pushengage' ),
				'manage_options',
				$upgrade_url
			);

			// Add a custom class and css to the 'Upgrade To Pro' menu
			if ( isset( $submenu['pushengage'][13] ) ) {
				$submenu['pushengage'][13][4] = 'pe-upgrade-to-pro-submenu';
			}
		}

		// Add custom class to integrations submenu.
		if ( isset( $submenu['pushengage'][5] ) ) {
			$submenu['pushengage'][5][4] = 'pe-menu-integrations';
		}

		// Add custom class for triggers submenu.
		if ( isset( $submenu['pushengage'][9] ) ) {
			$submenu['pushengage'][4][4] = 'pe-menu-triggers';
		}

		remove_submenu_page( 'pushengage', 'pushengage' );
	}

	/**
	 * Render admin menu & submenu page
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function render_admin_menu_view() {
		Pushengage::output_view( 'admin.php' );
	}

	/**
	 * Add custom script to open upgrade to pro menu  in a new tab/window
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function add_upgrade_to_pro_custom_script() {        ?>
		<style type='text/css'>
			a.pe-upgrade-to-pro-submenu {
				background-color: #00a32a !important;
				color: #fff !important;
				font-weight: 600 !important;
			}
		</style>
		<script type="text/javascript">
			document.addEventListener("DOMContentLoaded", function(){
				var link = document.querySelector('li.pe-admin-bar-upgrade-option a');
				if(link) {
					link.setAttribute('target', '_blank');
				}
			});
		</script>
		<?php
	}
}

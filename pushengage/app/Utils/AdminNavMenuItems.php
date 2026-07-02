<?php
namespace Pushengage\Utils;

use Pushengage\HttpClient;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminNavMenuItems {
	/**
	 * Get menu items for pushengage side bar menus & top navbar menu
	 *
	 * @since 4.0.5
	 *
	 * @param boolean $ignore_items
	 *
	 * @return array
	 */
	public static function get_menu_items( $menu_pos = 'adminmenu' ) {
		$is_admin_menu = ( 'adminmenu' === $menu_pos );

		// "NEW!" badge markup, shown next to Workflows.
		$new_badge = '<span style="padding-left: 2px;color: #f18200; vertical-align: super; font-size: 9px;"> NEW!</span>';

		// Group 1: Dashboard.
		$menu_items = array(
			array(
				'id'    => 'dashboard',
				'label' => esc_html__( 'Dashboard', 'pushengage' ),
				'url'   => '',
			),
		);

		// Group 2: Push Broadcasts, Drip, Triggers, Workflows.
		$menu_items[] = array(
			'id'    => 'campaigns',
			'label' => esc_html__( 'Push Broadcasts', 'pushengage' ),
			'url'   => 'campaigns/notifications',
		);

		if ( $is_admin_menu ) {
			$menu_items[] = array(
				'id'    => 'drip',
				'label' => esc_html__( 'Drip', 'pushengage' ),
				'url'   => 'automation/drip',
			);
			$menu_items[] = array(
				'id'    => 'triggered_campaign',
				'label' => esc_html__( 'Triggers', 'pushengage' ),
				'url'   => 'campaigns/triggers',
			);
			$menu_items[] = array(
				'id'    => 'workflows',
				'label' => esc_html__( 'Workflows', 'pushengage' ) . $new_badge,
				'url'   => 'campaigns/workflows',
			);
		}

		// Group 3: Design, Audience, Analytics.
		if ( $is_admin_menu ) {
			$menu_items[] = array(
				'id'    => 'design',
				'label' => esc_html__( 'Design', 'pushengage' ),
				'url'   => 'design',
			);
		}

		$menu_items[] = array(
			'id'    => 'audience',
			'label' => esc_html__( 'Audience', 'pushengage' ),
			'url'   => 'audience/subscribers',
		);
		$menu_items[] = array(
			'id'    => 'analytics',
			'label' => esc_html__( 'Analytics', 'pushengage' ),
			'url'   => 'analytics',
		);

		// Group 4: WooCommerce, WhatsApp, Chat Widgets.
		if ( $is_admin_menu ) {
			$menu_items[] = array(
				'id'    => 'woocommerce',
				'label' => esc_html__( 'WooCommerce', 'pushengage' ),
				'url'   => 'woocommerce/automation',
			);
			$menu_items[] = array(
				'id'    => 'whatsapp',
				'label' => esc_html__( 'WhatsApp', 'pushengage' ),
				'url'   => 'whatsapp/automation',
			);
			$menu_items[] = array(
				'id'    => 'chat-widgets',
				'label' => esc_html__( 'Chat Widgets', 'pushengage' ),
				'url'   => 'chat-widgets',
			);
		}

		// Group 5: Settings, About Us ( + Upgrade to Pro is appended later when on free plan ).
		$menu_items[] = array(
			'id'    => 'settings',
			'label' => esc_html__( 'Settings', 'pushengage' ),
			'url'   => 'settings/site-details',
		);

		if ( $is_admin_menu ) {
			$menu_items[] = array(
				'id'    => 'about-us',
				'label' => esc_html__( 'About Us', 'pushengage' ),
				'url'   => 'about-us',
			);
		}

		if ( ! $is_admin_menu ) {
			$menu_items[] = array(
				'id'    => 'pe-debug',
				'label' => esc_html__( 'Debug', 'pushengage' ),
				'url'   => 'debug',
			);
		}

		return $menu_items;
	}

	/**
	* Checks if we need to display the 'upgrade to Pro' submenu
	*
	* @since 4.0.0
	*
	* @return boolean
	*/
	public static function should_display_upgrade_submenu( $api_key ) {
		if ( empty( $api_key ) ) {
			return true;
		}

		$plan_type = get_transient( 'pe_subscription_plan_type' );
		if ( ! $plan_type ) {
			$plan_type = 'free';
			$site_info = HttpClient::get_site_info( $api_key );
			if ( ! empty( $site_info['owner']['paymentSubscription']['plan']['plan_type'] ) ) {
				$plan_type = $site_info['owner']['paymentSubscription']['plan']['plan_type'];
			}
			$ttl = 7 * DAY_IN_SECONDS;
			if ( 'free' === $plan_type ) {
				$ttl = 1 * DAY_IN_SECONDS;
			}
			set_transient( 'pe_subscription_plan_type', $plan_type, $ttl );
		}

		if ( 'free' === $plan_type ) {
			return true;
		}

		return false;
	}
}

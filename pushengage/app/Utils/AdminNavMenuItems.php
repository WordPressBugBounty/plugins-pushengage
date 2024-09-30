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
		$menu_items = array(
			array(
				'id'    => 'dashboard',
				'label' => esc_html__( 'Dashboard', 'pushengage' ),
				'url'   => '',
			),
			array(
				'id'    => 'campaigns',
				'label' => esc_html__( 'Push Broadcasts', 'pushengage' ),
				'url'   => 'campaigns/notifications',
			),
		);

		if ( 'adminmenu' === $menu_pos ) {
			$menu_items = array_merge(
				$menu_items,
				array(
					array(
						'id'    => 'drip',
						'label' => esc_html__( 'Drip', 'pushengage' ),
						'url'   => 'automation/drip',
					),
					array(
						'id'    => 'triggered_campaign',
						'label' => esc_html__( 'Triggers', 'pushengage' ),
						'url'   => 'campaigns/triggers',
					),
					array(
						'id'    => 'design',
						'label' => esc_html__( 'Design', 'pushengage' ),
						'url'   => 'design',
					),
				)
			);
		}

		$menu_items = array_merge(
			$menu_items,
			array(
				array(
					'id'    => 'audience',
					'label' => esc_html__( 'Audience', 'pushengage' ),
					'url'   => 'audience/subscribers',
				),
				array(
					'id'    => 'analytics',
					'label' => esc_html__( 'Analytics', 'pushengage' ),
					'url'   => 'analytics',
				),
				array(
					'id'    => 'settings',
					'label' => esc_html__( 'Settings', 'pushengage' ),
					'url'   => 'settings/site-details',
				),
				array(
					'id'    => 'integrations',
					'label' => esc_html__( 'Integrations', 'pushengage' ) . '<span style="padding-left: 2px;color: #f18200; vertical-align: super; font-size: 9px;"> NEW!</span>',
					'url'   => 'settings/integrations',
				),
			)
		);

		if ( 'adminmenu' === $menu_pos ) {
			$menu_items[] = array(
				'id'    => 'about-us',
				'label' => esc_html__( 'About Us', 'pushengage' ),
				'url'   => 'about-us',
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

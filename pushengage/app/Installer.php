<?php
namespace Pushengage;

use Pushengage\Upgrade;
use Pushengage\Utils\Options;
use Pushengage\Includes\WPMetricsTracker;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Installer {
	/**
	 * Trigger immediately after installing plugin
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function plugin_install() {
		$pushengage_settings = Options::get_site_settings();

		if ( empty( $pushengage_settings ) ) {
			$pushengage_settings = array(
				'api_key'                => '',
				'site_key'               => '',
				'site_id'                => '',
				'owner_id'               => '',
				'version'                => PUSHENGAGE_VERSION,
				'auto_push'              => true,
				'notification_icon_type' => 'featured_image',
				'featured_large_image'   => false,
				'multi_action_button'    => false,
				'category_segmentation'  => '',
			);

			add_option( 'pushengage_settings', $pushengage_settings );
		}

		Upgrade::plugin_upgrade();

		// Track WP metrics `activated_at` timestamp.
		$metrics_tracker = WPMetricsTracker::get_instance();
		$metrics_tracker->send_metrics(
			array(
				'status'       => 'active',
				'activated_at' => gmdate(
					'Y-m-d\TH:i:s\Z',
					time()
				),
			)
		);

		set_transient( 'pushengage_activation_redirect', true, 30 );
	}
}

<?php
namespace Pushengage;

use Pushengage\Upgrade;
use Pushengage\Utils\Options;

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

		set_transient( 'pushengage_activation_redirect', true, 30 );
	}
}

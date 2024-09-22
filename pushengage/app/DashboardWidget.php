<?php

namespace Pushengage;
use Pushengage\Utils\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DashboardWidget {


	/**
	 * Class constructor
	 *
	 * @since 4.0.5
	 *
	 * @return void
	 */
	public function __construct() {
		 $this->dashboard_widget_hook();
	}

	/**
	 * Implement admin dashboard widget hook
	 *
	 * @since 4.0.5
	 *
	 * @return void
	 */
	public function dashboard_widget_hook() {
		add_action( 'wp_dashboard_setup', array( $this, 'dashboard_widget' ) );
	}

	/**
	 * Loads a dashboard widget if the user has not connected with a site.
	 *
	 * @since 4.0.5
	 *
	 * @return void
	 */
	public function dashboard_widget() {
		// Get options from settings
		$pushengage_settings = Options::get_site_settings();
		$hide_widget = $pushengage_settings['misc']['hideDashboardWidget'];
		// Return if widget is disabled.
		if ( $hide_widget ) {
			return;
		}
		wp_add_dashboard_widget(
			'pe-dashboard-widget',
			esc_html__( 'PushEngage', 'pushengage' ),
			array( $this, 'dashboard_widget_callback' ),
			null,
			null,
			'normal',
			'high'
		);

		EnqueueAssets::enqueue_pushengage_scripts();
		EnqueueAssets::localize_script();
	}

	/**
	 * Dashboard widget callback.
	 *
	 * @since 4.0.5
	 *
	 * @return void
	 */
	public function dashboard_widget_callback() {
		Pushengage::output_view( 'dashboard-widget.php' );
	}
}

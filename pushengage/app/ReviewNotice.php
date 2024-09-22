<?php

namespace Pushengage;

use Pushengage\Utils\Options;
use Pushengage\Utils\NonceChecker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Review Plugin Notice.
 *
 * @since 4.0.5
 */
class ReviewNotice {
	/**
	 * Class constructor.
	 *
	 * @since 4.0.5
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'review_notice' ) );

		// Admin Ajax Calls
		add_action( 'wp_ajax_pe_dismiss_review_notice', array( $this, 'dismiss_review_notice' ) );
		add_action( 'wp_ajax_pe_get_review_notice', array( $this, 'get_review_notice' ) );
	}

	/**
	 * Display review notice only in Network Admin if in Multisite.
	 * Otherwise, display in Admin Dashboard.
	 *
	 * @since 4.0.5
	 *
	 * @return void
	 */
	public function review_notice() {
		if ( is_multisite() ) {
			add_action( 'network_admin_notices', array( $this, 'may_be_display_review_notice' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'may_be_display_review_notice' ) );
		}
	}

	/**
	 * request to render review notice
	 *
	 * @since 4.0.5
	 *
	 * @return void
	 */
	public function may_be_display_review_notice() {
		if ( self::should_render_review_notice() ) {
			Pushengage::output_view( 'review-notice.php' );
		}
	}

	/**
	 * Get Review Notice Settings
	 *
	 * @since 4.0.7
	 *
	 * @return $array
	 */
	public static function get_review_notice_settings() {
		$settings = get_user_meta( get_current_user_id(), 'pushengage_review_notice', true );

		if ( empty( $settings ) ) {
			return array(
				'clicked_review_action'    => '',
				'review_action_clicked_at' => 0,
			);
		}

		return $settings;
	}

	/**
	 * Check if review notice should be rendered
	 *
	 * @since 4.0.5
	 *
	 * @return boolean
	 */
	public static function should_render_review_notice() {
		$screen = get_current_screen();
		// Added filter for review notice allowed screens.
		$allowed_screens = apply_filters(
			'pushengage_review_notice_allowed_screens',
			array(
				'dashboard',
				'plugins',
			)
		);
		// Only show the review notice on Global WP Dashboard and Plugins page.
		if ( isset( $screen->id ) && ! in_array( $screen->id, $allowed_screens, true ) ) {
			return false;
		}

		// Only show to users that interact with our plugin.
		if ( ! current_user_can( 'publish_posts' ) ) {
			return false;
		}
		// Only show if the plugin is connected to a site.
		if ( ! Options::has_credentials() ) {
			return false;
		}

		$review_notice_settings = self::get_review_notice_settings();

		// If the notice has been dismissed, don't show it again.
		$clicked_review_action = $review_notice_settings['clicked_review_action'];
		if ( 'dismissed' === $clicked_review_action ) {
			return false;
		}

		// If clicked maybe later option, don't show it again for 30 days, after 30 days show again.
		if ( 'later' === $clicked_review_action ) {
			$clicked_at = $review_notice_settings['review_action_clicked_at'];
			return strtotime( '+30 days', $clicked_at ) < strtotime( 'now' );
		}

		$pushengage_settings = Options::get_site_settings();

		// If 'setup_time' is not present in 'pushengage_settings' options then get site info data from API
		// and use the site created date as 'setup_time'.
		$setup_time = $pushengage_settings['setup_time'];
		if ( ! $setup_time ) {
			$site_info = HttpClient::get_site_info( $pushengage_settings['api_key'] );
			if ( ! empty( $site_info['site']['created_at'] ) ) {
				$created_at = new \DateTime( $site_info['site']['created_at'] );
				$created_at->setTimezone( new \DateTimeZone( 'UTC' ) );
				$setup_time = $created_at->getTimestamp();
				$pushengage_settings['setup_time'] = $setup_time;
				Options::update_site_settings( $pushengage_settings );
			}
		}

		// Only show if site has been connected for over 15 days.
		if ( ! $setup_time || time() < strtotime( '+15 days', $setup_time ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Update review notice settings
	 *
	 * @param array $data
	 * @return void
	 */
	public static function update_review_notice_info( $data = array() ) {
		$settings = self::get_review_notice_settings();

		if ( isset( $data['clicked_review_action'] ) ) {
			$settings['clicked_review_action'] = $data['clicked_review_action'];
			$settings['review_action_clicked_at'] = time();
		}

		update_user_meta( get_current_user_id(), 'pushengage_review_notice', $settings );
	}

	/**
	 * Delete review notice settings from DB
	 *
	 * @since 4.0.7
	 *
	 * @return void
	 */
	public static function delete_review_notice_settings() {
		global $wpdb;

		$wpdb->query(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key IN (
				'pushengage_review_notice'
			)"
		);
	}

	/**
	 * Ajax handler to get review notice if available
	 *
	 * @since 4.0.5
	 *
	 * @return void
	 */
	public function get_review_notice() {
		NonceChecker::check();

		$data = null;
		if ( ReviewNotice::should_render_review_notice() ) {
			$data = array(
				'alert_id'    => strtotime( 'now' ),
				'title'       => __( 'Are you enjoying PushEngage?', 'pushengage' ),
				'message'     => __(
					'Hey, I noticed you have been using PushEngage for some time - that’s awesome! Could you please do me a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?',
					'pushengage'
				),
				'url'         => 'https://wordpress.org/support/plugin/pushengage/reviews/?filter=5#new-post',
				'type'        => 'success',
				'status'      => 1,
				'state'       => 'review',
				'create_date' => wp_date( 'Y-m-d H:i:s' ),
			);
		}

		wp_send_json_success( array( 'review' => $data ), 200 );
	}

	/**
	 * Dismiss review notice
	 *
	 * @since 4.0.5
	 *
	 * @return void
	 */
	public function dismiss_review_notice() {
		NonceChecker::check();

		if ( ! empty( $_POST['clicked_review_action'] ) ) {
			$data = array();
			$data['clicked_review_action'] = sanitize_text_field( $_POST['clicked_review_action'] );
			self::update_review_notice_info( $data );
		}

		wp_send_json_success();
	}
}

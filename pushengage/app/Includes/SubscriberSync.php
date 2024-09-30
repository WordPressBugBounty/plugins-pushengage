<?php

namespace Pushengage\Includes;

use Pushengage\Utils\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Subscriber Sync Class
 *
 * @since 4.0.10
 */
class SubscriberSync {
	/**
	 * Class constructor.
	 *
	 * @since 4.0.10
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_user_sync_script' ) );
		add_action( 'wp_ajax_pe_subscriber_sync', array( $this, 'sync_subscriber_data' ) );
	}

	/**
	 * Enqueue WP User Sync script.
	 *
	 * @since 4.0.10
	 * @return void
	 */
	public function enqueue_user_sync_script() {
		wp_register_script(
			'pushengage-subscriber-sync',
			PUSHENGAGE_PLUGIN_URL . 'assets/js/subscriber-sync.js',
			array( 'pushengage-sdk-init' ),
			PUSHENGAGE_VERSION,
			array(
				'in_footer' => true,
				'strategy' => 'defer',
			)
		);

		$user_id = get_current_user_id();
		$subscriber_ids = get_user_meta( $user_id, 'pushengage_subscriber_ids', true );
		if ( empty( $subscriber_ids ) ) {
			$subscriber_ids = array();
		}

		wp_localize_script(
			'pushengage-subscriber-sync',
			'pushengageSubscriberSync',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'pushengage_subscriber_sync_nonce' ),
				'subscriber_ids' => $subscriber_ids,
			)
		);

		wp_enqueue_script( 'pushengage-subscriber-sync' );
	}

	/**
	 * Ajax handler for synching subscriber data
	 *
	 * @since 4.0.10
	 *
	 * @return void
	 */
	public function sync_subscriber_data() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'pushengage_subscriber_sync_nonce' ) ) {
			wp_send_json_error( __( 'Nonce verification failed.', 'pushengage' ), 403 );
		};

		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			wp_send_json_error( __( 'User not logged in.', 'pushengage' ), 403 );
		}

		$new_subscriber_id      = isset( $_POST['add_id'] ) ? sanitize_text_field( wp_unslash( $_POST['add_id'] ) ) : '';
		$remove_subscriber_id   = isset( $_POST['remove_id'] ) ? sanitize_text_field( wp_unslash( $_POST['remove_id'] ) ) : '';

		$subscriber_ids = get_user_meta( $user_id, 'pushengage_subscriber_ids', true );
		if ( empty( $subscriber_ids ) ) {
			$subscriber_ids = array();
		}

		if ( ! empty( $remove_subscriber_id ) && in_array( $remove_subscriber_id, $subscriber_ids, true ) ) {
			$subscriber_ids = array_diff( $subscriber_ids, array( $remove_subscriber_id ) );
		}

		if ( ! empty( $new_subscriber_id ) && ! in_array( $new_subscriber_id, $subscriber_ids, true ) ) {
			$subscriber_ids[] = $new_subscriber_id;
		}

		// Keep only the latest 5 ids
		if ( count( $subscriber_ids ) > 5 ) {
			$subscriber_ids = array_slice( $subscriber_ids, -5 );
		}

		if ( empty( $subscriber_ids ) ) {
			delete_user_meta( $user_id, 'pushengage_subscriber_ids' );
		} else {
			update_user_meta( $user_id, 'pushengage_subscriber_ids', $subscriber_ids );
		}

		wp_send_json_success(
			array(
				'subscriber_ids' => $subscriber_ids,
			)
		);

	}
}

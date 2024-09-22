<?php
namespace Pushengage\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NonceChecker {
	/**
	 * Check nonce validity
	 *
	 * @param string $action
	 *
	 * @return void
	 */
	public static function check( $action = 'pushengage-nonce' ) {
		if ( ! check_ajax_referer( $action, '_wpnonce', false ) ) {
			$error['message'] = __( 'Invalid security token sent.', 'pushengage' );
			$error['code'] = 'invalid_security_token';
			wp_send_json_error( $error, 401 );
		}
	}

	/**
	 * Create nonce
	 *
	 * @since 4.0.5
	 *
	 * @param string $action
	 *
	 * @return void
	 */
	public static function create_nonce( $action = 'pushengage-nonce' ) {
		return wp_create_nonce( $action );
	}
}

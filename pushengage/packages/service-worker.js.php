<?php
/**
 * Note: This file is intended to be publicly accessible.
 * Reference: https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API/Using_Service_Workers
 */

header( 'Service-Worker-Allowed: /' );
header( 'Content-Type: application/javascript' );
header( 'X-Robots-Tag: none' );


$pushengage_app_id = '';
if ( array_key_exists( 'appId', $_GET ) ) {
	$pushengage_app_id = filter_var( $_GET['appId'], FILTER_SANITIZE_STRING );
}

// Validate that the app_id contains only valid characters
if ( ! empty( $pushengage_app_id ) && preg_match( '/^[a-zA-Z0-9-_]+$/', $pushengage_app_id ) ) {
	echo "var PUSHENGAGE_APP_ID = '" . htmlspecialchars( $pushengage_app_id, ENT_QUOTES, 'UTF-8' ) . "';";
	echo "importScripts('https://clientcdn.pushengage.com/sdks/service-worker.js');";
	exit;
}

$subdomain = '';
if ( array_key_exists( 'domain', $_GET ) ) {
	$subdomain = filter_var( $_GET['domain'], FILTER_SANITIZE_STRING );
}

// Validate that the subdomain contains only valid characters
if ( ! empty( $subdomain ) && preg_match( '/^[a-zA-Z0-9-]+$/', $subdomain ) ) {
	echo "importScripts('https://" . htmlspecialchars( $subdomain, ENT_QUOTES, 'UTF-8' ) . ".pushengage.com/service-worker.js');";
	exit;
}

echo "console.error('Invalid service worker request URL. Missing or invalid domain or app_id.')";

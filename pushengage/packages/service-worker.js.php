<?php
/**
 * Note: This file is intended to be publicly accessible.
 * Reference: https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API/Using_Service_Workers
 */

header( 'Service-Worker-Allowed: /' );
header( 'Content-Type: application/javascript' );
header( 'X-Robots-Tag: none' );


$pushengage_app_id = '';
if ( array_key_exists( 'app_id', $_GET ) ) {
	$pushengage_app_id = filter_var( $_GET['app_id'], FILTER_SANITIZE_STRING );
}

if ( ! empty( $pushengage_app_id ) ) {
	echo "var PUSHENGAGE_APP_ID = '" . $pushengage_app_id . "';";
	echo "importScripts('https://clientcdn.pushengage.com/sdks/service-worker.js');";
	exit;
}

$subdomain = '';
if ( array_key_exists( 'domain', $_GET ) ) {
	$subdomain = urlencode( $_GET['domain'] );
}

if ( ! empty( $subdomain ) ) {
	echo "importScripts('https://" . $subdomain . ".pushengage.com/service-worker.js?ver=2.3.0');";
	exit;
}

echo "console.error('Invalid service worker request URL. Missing domain or app_id.')";

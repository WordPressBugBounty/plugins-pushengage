<?php
/**
 * Plugin Name: PushEngage
 * Plugin URI: https://www.pushengage.com/?utm_source=WordPress&utm_campaign=Plugin&utm_medium=pluginHeader&utm_content=plugin-uri-link
 * Description: This plugin will push notifications for Chrome, Firefox, Opera, Microsoft Edge, Safari, UC Browser and Samsung Internet browsers.
 * Author: PushEngage
 * Author URI: https://www.pushengage.com/?utm_source=WordPress&utm_campaign=Plugin&utm_medium=pluginHeader&utm_content=author-uri-link
 *
 * Version: 4.0.10
 * Requires at least: 4.5.0
 * Requires PHP: 5.6
 *
 * Text Domain: pushengage
 * Domain Path: /languages
 *
 * License: GPLv2 or later.
 *
 * PushEngage is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * PushEngage is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PushEngage. If not, see <https://www.gnu.org/licenses/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PUSHENGAGE_VERSION' ) ) {
	define( 'PUSHENGAGE_VERSION', '4.0.10' );
}

if ( ! defined( 'PUSHENGAGE_FILE' ) ) {
	define( 'PUSHENGAGE_FILE', __FILE__ );
}

if ( ! defined( 'PUSHENGAGE_PLUGIN_URL' ) ) {
	define( 'PUSHENGAGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'PUSHENGAGE_PLUGIN_PATH' ) ) {
	define( 'PUSHENGAGE_PLUGIN_PATH', dirname( __FILE__ ) );
}

if ( ! defined( 'PUSHENGAGE_VIEWS_PATH' ) ) {
	define( 'PUSHENGAGE_VIEWS_PATH', PUSHENGAGE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'views' );
}

if ( ! defined( 'PUSHENGAGE_ASSETS_PATH' ) ) {
	define( 'PUSHENGAGE_ASSETS_PATH', PUSHENGAGE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'assets' );
}
// define remote url if not defined
if ( ! defined( 'PUSHENGAGE_API_URL' ) ) {
	/**
	 * PushEngage API base URL with trailing slash.
	 */
	define( 'PUSHENGAGE_API_URL', 'https://a.pusheapi.com/d/v1/' );
}
// define remote url if not defined
if ( ! defined( 'PUSHENGAGE_REST_API_URL' ) ) {
	/**
	 * PushEngage REST API base URL with trailing slash.
	 */
	define( 'PUSHENGAGE_REST_API_URL', 'https://api.pushengage.com/apiv1/' );
}
// define client js url if not defined
if ( ! defined( 'PUSHENGAGE_CLIENT_JS_URL' ) ) {
	/**
	 * PushEngage client cdn URL with trailing slash.
	 */
	define( 'PUSHENGAGE_CLIENT_JS_URL', 'https://clientcdn.pushengage.com/' );
}

// define client js url if not defined
if ( ! defined( 'PUSHENGAGE_APP_DASHBOARD_URL' ) ) {
	/**
	 * PushEngage app dashboard URL without trailing slash.
	 */
	define( 'PUSHENGAGE_APP_DASHBOARD_URL', 'https://app.pushengage.com' );
}

/**
 * Kick off the pushengage plugin
 *
 * @since 4.0.0
 */
class_exists( 'Pushengage\Pushengage' ) || require_once __DIR__ . '/vendor/autoload.php';

use Pushengage\Pushengage;
use Pushengage\Includes\Api\PushengageAPI;

Pushengage::instance();

/**
 * Get the main instance of PushEngage API.
 * This is used as an interface to let addons and other plugins to access
 * pushengage api.
 *
 * @since 4.0.10
 * @return PushEngageAPI
 */
function pushengage() {
	return PushengageAPI::instance();
}

do_action( 'pushengage_loaded' );

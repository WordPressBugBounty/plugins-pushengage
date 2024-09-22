<?php
namespace Pushengage\Utils;
use Pushengage\Utils\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helpers {
	/**
	 * Returns Jed-formatted localization data. Added for backwards-compatibility.
	 *
	 * @since 4.0.0
	 *
	 * @param  string $domain Translation domain.
	 * @return array          The information of the locale.
	 */
	public static function get_jed_locale_data( $domain ) {
		$translations = get_translations_for_domain( $domain );
		$translations2 = get_translations_for_domain( 'default' );

		$locale = array(
			'' => array(
				'domain' => $domain,
				'lang'   => is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale(),
			),
		);

		if ( ! empty( $translations->headers['Plural-Forms'] ) ) {
			$locale['']['plural_forms'] = $translations->headers['Plural-Forms'];
		}

		foreach ( $translations->entries as $msgid => $entry ) {
			$locale[ $msgid ] = $entry->translations;
		}

		// If any of the translated strings incorrectly contains HTML line breaks, we need to return or else the admin is no longer accessible.
		$json = wp_json_encode( $locale );
		if ( preg_match( '/<br[\s\/\\\\]*>/', $json ) ) {
			return array();
		}

		return $locale;
	}

	/**
	 * Checks if a plugin is active
	 *
	 * @since 4.0.0
	 *
	 * @param  string  $basename The plugin basename.
	 * @return boolean Whether or not the plugin is active.
	 */
	public static function is_plugin_active( $basename ) {
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
		return in_array( $basename, $active_plugins );
	}

	/**
	 * Get the active caching plugin details
	 *
	 * @since 4.0.0
	 *
	 * @return array|null   The active active caching plugin details returns null if no caching is active
	 */
	public static function get_active_caching_plugin() {
		// Array of popular caching plugin slugs
		$caching_plugins = array(
			array(
				'title' => 'WP Rocket',
				'basename' => 'wp-rocket/wp-rocket.php',
			),
			array(
				'title' => 'W3 Super Cache',
				'basename' => 'wp-super-cache/wp-cache.php',
			),
			array(
				'title' => 'W3 Total Cache',
				'basename' => 'w3-total-cache/w3-total-cache.php',
			),
			array(
				'title' => 'Comet Cache',
				'basename' => 'comet-cache/comet-cache.php',
			),
			array(
				'title' => 'WP fastest Cache',
				'basename' => 'wp-fastest-cache/wpFastestCache.php',
			),
			array(
				'title' => 'Cache Enabler',
				'basename' => 'cache-enabler/cache-enabler.php',
			),
			array(
				'title' => 'Hyper Cache',
				'basename' => 'hyper-cache/plugin.php',
			),
			array(
				'title' => 'SiteGround Optimizer',
				'basename' => 'sg-cachepress/sg-cachepress.php',
			),
		);

		// Loop through each plugin and check if it's active
		$active_caching_plugin = null;
		foreach ( $caching_plugins as $plugin ) {
			if ( self::is_plugin_active( $plugin['basename'] ) ) {
				$active_caching_plugin = $plugin;
				break;
			}
		}
		return $active_caching_plugin;
	}

	/**
	 * Checks if the website is using SSl or not.
	 *
	 * @since 4.0.4.1
	 *
	 * @return boolean
	 */
	public static function is_ssl() {
		// cloudflare
		if ( ! empty( $_SERVER['HTTP_CF_VISITOR'] ) ) {
			$cfo = json_decode( $_SERVER['HTTP_CF_VISITOR'] );
			if ( isset( $cfo->scheme ) && 'https' === $cfo->scheme ) {
				return true;
			}
		}

		// other proxy
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
			return true;
		}

		return function_exists( 'is_ssl' ) ? is_ssl() : false;
	}

	/**
	 * Returns whether the block editor is loading on the current screen.
	 *
	 * @since 4.0.5.1
	 *
	 * @return boolean|null True if the block editor is being loaded, false if
	 *                      not loading and null if can not determine.
	 */
	public static function is_block_editor() {
		$curr_screen = get_current_screen();
		if ( ! empty( $curr_screen ) && method_exists( $curr_screen, 'is_block_editor' ) ) {
			return $curr_screen->is_block_editor();
		}
		return null;
	}

	/**
	 * Determines if given URL is a valid HTTP or HTTPS URLs and length is
	 * less than or equal to max_len.
	 *
	 * @since 4.0.5.1
	 *
	 * @param string $url URL to check.
	 * @param number $max_len Maximum allowed length of the URL.
	 *
	 * @return bool false on failure.
	 */
	public static function is_http_or_https_url( $url, $max_len = 0 ) {
		if ( empty( $url ) ) {
			return false;
		}

		if ( 'http' === substr( $url, 0, 4 ) || 'https' === substr( $url, 0, 5 ) ) {
			if ( $max_len && strlen( $url ) > $max_len ) {
				return false;
			}
			return true;
		}

		return false;
	}



	/**
	 * Decodes JSON string as associative array.
	 *
	 * @param string $data
	 * @return mixed|null
	 */
	public static function json_decode( $data ) {
		$flag = 0;
		if ( defined( 'JSON_INVALID_UTF8_IGNORE' ) ) {
			// phpcs:ignore PHPCompatibility.Constants.NewConstants.json_invalid_utf8_ignoreFound
			$flag = JSON_INVALID_UTF8_IGNORE | $flag;
		}
		return json_decode( $data, true, 512, $flag );
	}

	/**
	 * Convert HTML entities to their corresponding characters
	 *
	 * @since 4.0.6
	 * @param string The input string
	 * @return string The decoded string
	 */
	public static function decode_entities( $string ) {
		$flag = ENT_QUOTES;
		if ( defined( 'ENT_HTML401' ) ) {
			$flag = ENT_HTML401 | $flag;
		}
		return html_entity_decode( str_replace( array( '&apos;', '&#x27;', '&#39;', '&quot;' ), '\'', $string ), $flag, 'UTF-8' );
	}

	/**
	 * Retrieves the pe_post_options post meta field for the given post ID.
	 *
	 * @since 4.0.6
	 * @param int $post_id Post ID
	 * @return mixed The push options post meta
	 */
	public static function get_push_options_post_meta( $post_id ) {
		$push_options = array();
		$post_meta = get_post_meta( $post_id, 'pe_push_options', true );
		if ( ! empty( $post_meta ) ) {
			// Prior to 4.0.6 pe_push_options was stored as json string which was causing issues
			// with special characters decoding. Now we store the serialized array in metadata.
			// So we need to check if the value is a string or array.
			if ( is_string( $post_meta ) ) {
				$push_options = Helpers::json_decode( $post_meta );
			} else {
				$push_options = $post_meta;
			}
		}

		return $push_options;
	}

	/**
	* Get image from post
	*
	* @since 4.0.8
	*
	* @param string|int[] $size
	* @param number $post_id
	*
	* @return string
	*/
	public static function get_post_image( $size, $post_id ) {
		$image_url = '';

		if ( has_post_thumbnail( $post_id ) ) {
			$raw_image = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), $size );
			if ( ! empty( $raw_image ) ) {
				$image_url = ! empty( $raw_image[0] ) ? $raw_image[0] : '';
			}
		}

		return $image_url;
	}

	/**
	 * Check if WooCommerce integration settings enabled.
	 *
	 * @since 4.0.9
	 * @return boolean
	 */
	public static function is_woocommerce_integrated() {
		$settings = Options::get_site_settings();

		if ( empty( $settings['woo_integration'] ) ) {
			return false;
		}

		$integration_settings = $settings['woo_integration'];

		return (
			! empty( $integration_settings['cart_abandonment']['enable'] ) &&
			! empty( $integration_settings['cart_abandonment']['name'] )
		) || (
			! empty( $integration_settings['browse_abandonment']['enable'] ) &&
			! empty( $integration_settings['browse_abandonment']['name'] )
		);

	}

}

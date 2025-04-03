<?php
namespace Pushengage\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contains string specific helper methods.
 *
 * @since 4.0.8.1
 */
class StringUtils {


	/**
	 * Returns the substring with a given start index and length.
	 *
	 * @since 4.0.8.1
	 *
	 * @param  string $string   The string.
	 * @param  int    $start    The start index.
	 * @param  int    $length   The length.
	 * @return string           The substring.
	 */
	public static function substr( $string, $start, $length = null ) {
		return substr( $string, $start, $length );
	}
}

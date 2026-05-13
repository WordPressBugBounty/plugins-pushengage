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
	 * Uses mb_substr when available for proper multibyte support.
	 *
	 * @since 4.0.8.1
	 *
	 * @param  string $string   The string.
	 * @param  int    $start    The start index.
	 * @param  int    $length   The length.
	 * @return string           The substring.
	 */
	public static function substr( $string, $start, $length = null ) {
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $string, $start, $length, 'UTF-8' );
		}
		return substr( $string, $start, $length );
	}

	/**
	 * Returns the length of a string.
	 * Uses mb_strlen when available for proper multibyte support.
	 *
	 * @since 4.0.8.1
	 *
	 * @param  string $string The string.
	 * @return int            The length.
	 */
	public static function strlen( $string ) {
		if ( function_exists( 'mb_strlen' ) ) {
			return mb_strlen( $string, 'UTF-8' );
		}
		return strlen( $string );
	}
}

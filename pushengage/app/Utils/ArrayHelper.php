<?php
namespace Pushengage\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ArrayAccess;

class ArrayHelper {

	/**
	 * Determine if the given key exists in the provided array.
	 *
	 * @since 4.0.0
	 *
	 * @param  \ArrayAccess|array  $array
	 * @param  string|int  $key
	 *
	 * @return bool
	 */
	public static function exists( $array, $key ) {
		if ( $array instanceof ArrayAccess ) {
			return $array->offsetExists( $key );
		}

		return array_key_exists( $key, $array );
	}

	/**
	 * Determine whether the given value is array accessible.
	 *
	 * @since 4.0.0
	 *
	 * @param  mixed  $value
	 *
	 * @return bool
	 */
	public static function accessible( $value ) {
		return is_array( $value ) || $value instanceof ArrayAccess;
	}

	/**
	 * Get an item from an array using "dot" notation. Returns default value  if  value is null
	 * or $key is not present in the array
	 *
	 * @since 4.0.0
	 *
	 * @param  \ArrayAccess|array  $array
	 * @param  string|int|null  $key
	 * @param  mixed  $default
	 *
	 * @return mixed
	*/
	public static function get( $array, $key, $default = null ) {
		if ( ! static::accessible( $array ) ) {
			return $default;
		}

		$value = null;

		if ( is_null( $key ) ) {
			$value = $array;
		}

		if ( static::exists( $array, $key ) ) {
			$value = $array[ $key ];
		}

		if ( strpos( $key, '.' ) === false ) {
			$value = isset( $array[ $key ] ) ? $array[ $key ] : $default;
		}

		foreach ( explode( '.', $key ) as $segment ) {
			if ( static::accessible( $array ) && static::exists( $array, $segment ) ) {
				$value = $array[ $segment ];
				$array = $array[ $segment ];
			} else {
				$value = $default;
				break;
			}
		}

		return ! is_null( $value ) ? $value : $default;
	}

	/**
	 * Get a subset of the items from the given array.
	 *
	 * @since 4.0.0
	 *
	 * @param  array  $array
	 * @param  array|string  $keys
	 *
	 * @return array
	 */
	public static function only( $array, $keys ) {
		return array_intersect_key( $array, array_flip( (array) $keys ) );
	}
}

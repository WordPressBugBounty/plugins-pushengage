<?php

namespace Pushengage\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PublicPostTypes {

	/**
	 * Get all publicly accessible post types
	 *
	 * @since 4.0.5
	 *
	 * @return array
	 */
	public static function get_all() {
		$args    = array(
			'public' => true,
		);

		$post_types = get_post_types( $args, 'objects' );

		// Ignore attachment post type.
		if ( isset( $post_types['attachment'] ) ) {
			unset( $post_types['attachment'] );
		}

		$formatted_post_types = array();

		foreach ( $post_types as $post_type ) {
			array_push(
				$formatted_post_types,
				array(
					'name'     => $post_type->name,
					'label'    => $post_type->labels->singular_name,
					'value'    => $post_type->name,
				)
			);
		}

		return $formatted_post_types;
	}
}

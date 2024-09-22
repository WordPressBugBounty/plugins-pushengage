<?php
/**
 * Faux Block init.
 */
namespace Pushengage\Libraries\AMFB;

defined( 'WPINC' ) || die;
/**
 * Init Faux Block Class.
 *
 * @since 4.0.9
 */
class InitFauxBlock {
	/**
	 * Load Faux blocks.
	 *
	 * @since 4.0.9
	 * @return void
	 */
	public static function load() {
		if ( ! class_exists( 'PushEngage\Libraries\AMFB\Inc\FauxBlock' ) ) {
			require_once __DIR__ . '/inc/faux-block.php';
			if ( class_exists( 'PushEngage\Libraries\AMFB\Inc\FauxBlock' ) ) {
				$faux_blocks = new Inc\FauxBlock();
				$faux_blocks->setup();
			}
		}
	}
}

<?php
/**
 * Bootstraps PushEngage abilities for the WordPress Abilities API (WP 6.9+).
 *
 * Each ability domain (notifications, segments, analytics, settings,
 * automations, whatsapp, debug, plugin info) lives in its own registrar under
 * app/Integrations/Abilities/. This class only wires the WP hooks and
 * instantiates the registrars when the API initializes.
 *
 * @since 4.2.2
 */

namespace Pushengage\Integrations;

use Pushengage\Integrations\Abilities\AnalyticsAbilities;
use Pushengage\Integrations\Abilities\AutomationAbilities;
use Pushengage\Integrations\Abilities\DebugAbilities;
use Pushengage\Integrations\Abilities\NotificationAbilities;
use Pushengage\Integrations\Abilities\PluginInfoAbilities;
use Pushengage\Integrations\Abilities\SegmentAbilities;
use Pushengage\Integrations\Abilities\SettingsAbilities;
use Pushengage\Integrations\Abilities\WhatsappAbilities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Abilities
 *
 * @since 4.2.2
 */
class Abilities {

	/**
	 * Domain registrars instantiated when the Abilities API initializes.
	 * Held on the instance so the registered execute callbacks (which bind
	 * `array( $registrar, 'execute_*' )`) keep their object alive.
	 *
	 * @var \Pushengage\Integrations\Abilities\AbstractRegistrar[]
	 */
	private $registrars = array();

	/**
	 * Constructor. Hooks into the WordPress Abilities API lifecycle.
	 *
	 * @since 4.2.2
	 */
	public function __construct() {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Register the pushengage ability category.
	 *
	 * @since 4.2.2
	 * @return void
	 */
	public function register_category() {
		wp_register_ability_category(
			'pushengage',
			array(
				'label'       => __( 'PushEngage', 'pushengage' ),
				'description' => __(
					'Send and manage push notifications, segments, and WooCommerce / WhatsApp automations.',
					'pushengage'
				),
			)
		);
	}

	/**
	 * Instantiate every registrar and register their abilities.
	 *
	 * @since 4.2.2
	 * @return void
	 */
	public function register_abilities() {
		$this->registrars = array(
			new PluginInfoAbilities(),
			new NotificationAbilities(),
			new SegmentAbilities(),
			new AnalyticsAbilities(),
			new SettingsAbilities(),
			new AutomationAbilities(),
			new WhatsappAbilities(),
			new DebugAbilities(),
		);

		foreach ( $this->registrars as $registrar ) {
			$registrar->register();
		}
	}
}

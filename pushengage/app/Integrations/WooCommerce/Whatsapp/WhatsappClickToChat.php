<?php

namespace Pushengage\Integrations\WooCommerce\Whatsapp;

use Pushengage\Utils\Options;

/**
 * WhatsApp Click To Chat
 *
 * @since 4.1.2
 */
class WhatsappClickToChat {
	/**
	 * Instance of this class
	 *
	 * @since 4.1.2
	 * @var object
	 */
	private static $instance;

	/**
	 * The WhatsApp click to chat settings
	 *
	 * @since 4.1.2
	 * @var array
	 */
	private $settings;

	/**
	 * Initialize the class
	 *
	 * @since 4.1.2
	 */
	public function __construct() {
		$this->settings = Options::get_whatsapp_click_to_chat_settings();

		// Only add frontend actions if feature is enabled
		if ( isset( $this->settings['enabled'] ) && $this->settings['enabled'] && ! empty( $this->settings['phoneNumber'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'wp_footer', array( $this, 'render_chat_button' ) );
		}
	}

	/**
	 * Get an instance of this class
	 *
	 * @since 4.1.2
	 *
	 * @return WhatsappClickToChat
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Enqueue scripts and styles for the chat button
	 *
	 * @since 4.1.2
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_style(
			'pushengage-whatsapp-click-to-chat',
			PUSHENGAGE_PLUGIN_URL . 'assets/css/whatsapp-click-to-chat.css',
			array(),
			PUSHENGAGE_VERSION
		);
	}

	/**
	 * Render the WhatsApp chat button in the footer
	 *
	 * @since 4.1.2
	 *
	 * @return void
	 */
	public function render_chat_button() {
		$phone_number = preg_replace( '/[^0-9]/', '', $this->settings['phoneNumber'] );
		if ( empty( $phone_number ) ) {
			return;
		}

		// Get the greeting message and URL encode it
		$greeting_message = ! empty( $this->settings['greetingMessage'] )
			? urlencode( $this->settings['greetingMessage'] )
			: urlencode( 'Hello' );

		// Get the button style
		$button_style = isset( $this->settings['buttonStyle'] ) ? $this->settings['buttonStyle'] : 'style1';

		// Get the button size (default to 48 if not set)
		$button_size = isset( $this->settings['buttonSize'] ) && is_numeric( $this->settings['buttonSize'] )
			? intval( $this->settings['buttonSize'] )
			: 48;

		// Get the button position (default to 'bottom-right' if not set)
		$button_position = isset( $this->settings['buttonPosition'] ) ? $this->settings['buttonPosition'] : 'bottom-right';

		// Get the offsets (default to 20 if not set)
		$horizontal_offset = isset( $this->settings['horizontalOffset'] ) && is_numeric( $this->settings['horizontalOffset'] )
			? intval( $this->settings['horizontalOffset'] )
			: 20;
		$vertical_offset = isset( $this->settings['verticalOffset'] ) && is_numeric( $this->settings['verticalOffset'] )
			? intval( $this->settings['verticalOffset'] )
			: 20;

		// Get the z-index (default to 9999 if not set)
		$z_index = isset( $this->settings['zIndex'] ) && is_numeric( $this->settings['zIndex'] )
			? intval( $this->settings['zIndex'] )
			: 9999;

		// Build the WhatsApp URL
		$whatsapp_url = "https://wa.me/{$phone_number}?text={$greeting_message}";

		// Prepare inline styles for button size
		$button_size_style = ' style="width: ' . $button_size . 'px; height: ' . $button_size . 'px;"';
		$icon_size = round( $button_size / 1.5 ); // Adjust icon size ratio to match preview
		$icon_size_style = ' style="width: ' . $icon_size . 'px; height: ' . $icon_size . 'px;"';

		// Prepare position styles
		$position_style = $this->get_position_style( $button_position, $horizontal_offset, $vertical_offset, $z_index );

		// Output the button based on style
		echo '<div class="pe-whatsapp-click-to-chat pe-whatsapp-' . esc_attr( $button_style ) . ' pe-whatsapp-position-' . esc_attr( $button_position ) . '"' . $position_style . '>';
		echo '<a href="' . esc_url( $whatsapp_url ) . '" target="_blank" rel="noopener" class="pe-whatsapp-button"' . $button_size_style . '>';
		echo '<span class="pe-whatsapp-icon"' . $icon_size_style . '></span>';
		echo '</a>';
		echo '</div>';
	}

	/**
	 * Get position style based on button position and offsets
	 *
	 * @since 4.1.2
	 *
	 * @param string $position The button position
	 * @param int    $horizontal_offset The horizontal offset
	 * @param int    $vertical_offset The vertical offset
	 * @param int    $z_index The z-index value
	 *
	 * @return string The inline style string
	 */
	private function get_position_style( $position, $horizontal_offset, $vertical_offset, $z_index ) {
		$styles = array();

		// Add z-index to all positions
		$styles[] = 'z-index: ' . $z_index;

		switch ( $position ) {
			case 'bottom-left':
				$styles[] = 'bottom: ' . $vertical_offset . 'px';
				$styles[] = 'left: ' . $horizontal_offset . 'px';
				break;
			case 'left-middle':
				$styles[] = 'left: ' . $horizontal_offset . 'px';
				$styles[] = 'top: 50%';
				$styles[] = 'transform: translateY(-50%)';
				break;
			case 'right-middle':
				$styles[] = 'right: ' . $horizontal_offset . 'px';
				$styles[] = 'top: 50%';
				$styles[] = 'transform: translateY(-50%)';
				break;
			case 'bottom-right':
			default:
				$styles[] = 'bottom: ' . $vertical_offset . 'px';
				$styles[] = 'right: ' . $horizontal_offset . 'px';
				break;
		}

		return ' style="' . implode( '; ', $styles ) . ';"';
	}
}

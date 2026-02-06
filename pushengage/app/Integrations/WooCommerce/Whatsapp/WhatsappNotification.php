<?php

namespace Pushengage\Integrations\WooCommerce\Whatsapp;

use Pushengage\Integrations\WooCommerce\Whatsapp\CartAbandonment\CartAbandonmentTracker;
use Pushengage\Logger;
use Pushengage\Utils\Options;
use Pushengage\Pushengage;

/**
 * Class WhatsappNotification
 *
 * Handles WhatsApp notifications for WooCommerce
 */
class WhatsappNotification {
	private static $instance = null;
	private $whatsapp_cloud_api;
	private $campaigns;
	private $settings;
	private $logger;

	private $campaign_id_to_name = array(
		'cart_abandoned'   => 'Cart Abandoned',
		'order_placed'     => 'New Order',
		'order_processing' => 'Order Processing',
		'order_on_hold'    => 'Order On Hold',
		'order_completed'  => 'Order Completed',
		'order_cancelled'  => 'Order Cancelled',
		'order_failed'     => 'Order Failed',
		'order_refunded'   => 'Order Refunded',
	);

	/**
	 * Get singleton instance of WhatsappNotification
	 *
	 * @return WhatsappNotification Instance of WhatsappNotification
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->logger = Logger::get_instance( 'PushEngage' );
		$this->whatsapp_cloud_api = new WhatsappCloudApi();
		$this->load_settings();
		$this->init();
	}

	/**
	 * Load WhatsApp settings and campaigns from options
	 */
	private function load_settings() {
		// Load WhatsApp settings
		$this->settings = Options::get_whatsapp_settings();

		// Load WhatsApp automation campaigns
		$this->campaigns = Options::get_whatsapp_automation_campaigns();
	}

	/**
	 * Initialize hooks and filters
	 */
	public function init() {
		if ( class_exists( 'WooCommerce' ) ) {
			add_action( 'woocommerce_new_order', array( $this, 'send_new_order_notification' ), 10, 4 );
			add_action( 'woocommerce_order_status_processing', array( $this, 'send_processing_order_notification' ), 10, 4 );
			add_action( 'woocommerce_order_status_on-hold', array( $this, 'send_on_hold_order_notification' ), 10, 4 );
			add_action( 'woocommerce_order_status_completed', array( $this, 'send_completed_order_notification' ), 10, 4 );
			add_action( 'woocommerce_order_status_cancelled', array( $this, 'send_cancelled_order_notification' ), 10, 4 );
			add_action( 'woocommerce_order_status_failed', array( $this, 'send_failed_order_notification' ), 10, 4 );
			add_action( 'woocommerce_order_status_refunded', array( $this, 'send_refunded_order_notification' ), 10, 4 );

			if ( $this->is_cart_abandonment_campaign_enabled() ) {
				// Add hook for abandoned cart notification
				add_action( 'pushengage_send_abandoned_cart_notification', array( $this, 'send_abandoned_cart_notification' ), 10, 1 );
				// Initialize Cart Abandonment Tracker
				CartAbandonmentTracker::get_instance();
			} else {
				wp_clear_scheduled_hook( 'pushengage_check_abandoned_carts' );
			}

			if ( is_admin() ) {
				// Display admin notice if decrypted whatsapp credential access token is invalid
				if ( isset( $this->settings['isDecryptedAccessTokenValid'] ) && ! $this->settings['isDecryptedAccessTokenValid'] ) {
					add_action( 'admin_notices', array( $this, 'display_whatsapp_credentials_invalid_notice' ) );
				}
			}
		}
	}

	/**
	 * Display admin notice if decrypted whatsapp credential access token is invalid
	 *
	 * @since 4.1.2
	 *
	 * @return void
	 */
	public function display_whatsapp_credentials_invalid_notice() {
		if ( current_user_can( 'manage_options' ) ) {
			if ( isset( $this->settings['isDecryptedAccessTokenValid'] ) && ! $this->settings['isDecryptedAccessTokenValid'] ) {
				Pushengage::output_view( 'encryption-key-mismatch-error-notice.php' );
			}
		}
	}

	/**
	 * Check if cart abandonment campaign is enabled
	 *
	 * @since 4.1.2
	 *
	 * @return bool True if cart abandonment campaign is enabled, false otherwise
	 */
	public function is_cart_abandonment_campaign_enabled() {
		return isset( $this->campaigns['cart_abandoned'] ) && $this->campaigns['cart_abandoned']['enabled'] && $this->campaigns['cart_abandoned']['customerConfig']['enabled'];
	}

	/**
	 * Send WhatsApp notification to customer
	 *
	 * @since 4.1.2
	 *
	 * @param WC_Order $order Order object
	 * @param array $campaign Campaign settings
	 */
	private function send_customer_order_notification( $order, $campaign ) {
		// Check if customerConfig exists
		if ( ! isset( $campaign['customerConfig'] ) ) {
			return;
		}

		// Check if customer notification is enabled
		if ( ! isset( $campaign['customerConfig']['enabled'] ) || ! $campaign['customerConfig']['enabled'] ) {
			return;
		}

		// Check if template name and language are set
		if ( empty( $campaign['customerConfig']['templateName'] ) || empty( $campaign['customerConfig']['templateLanguage'] ) ) {
			return;
		}

		// Get recipient phone number based on recipientType
		$recipient_type = isset( $campaign['customerConfig']['recipientType'] ) ? $campaign['customerConfig']['recipientType'] : 'billing';
		$phone_numbers = WhatsappHelper::get_customer_phone_number_from_order( $order, $recipient_type );

		// If no phone number, return
		if ( empty( $phone_numbers ) ) {
			return;
		}

		// Get replacement variables
		$replacements = WhatsappHelper::get_replacement_variables( $order, array() );
		// Format template components
		$components = $this->build_template_message_components( $campaign['customerConfig'], $replacements );

		// Send the message
		$success_numbers = array();
		$error_messages = array();
		foreach ( $phone_numbers as $phone_number ) {
			try {
				$result = $this->whatsapp_cloud_api->send_template_message( $phone_number, $campaign['customerConfig']['templateName'], $campaign['customerConfig']['templateLanguage'], $components );
				if ( $result['success'] ) {
					$success_numbers[] = $phone_number;
				} else {
					$this->logger->error( 'Failed to send customer order notification to ' . $phone_number . ':' . json_encode( $result ) );
					$error_messages[] = WhatsappHelper::format_whatsapp_error_message( $phone_number, $result );
				}
			} catch ( \Exception $e ) {
				$this->logger->error( 'Error sending customer order notification to ' . $phone_number . ':' . $e->getMessage() );
				$error_messages[] = WhatsappHelper::format_whatsapp_error_message( $phone_number, $e );
			}
		}

		// If some numbers were sent, add a note
		if ( ! empty( $success_numbers ) ) {
			$order_note = $this->campaign_id_to_name[ $campaign['id'] ] . ' - WhatsApp notification sent to ' . implode( ', ', $success_numbers );
			$order->add_order_note( $order_note );
		}

		// If some numbers were failed, add a note
		if ( ! empty( $error_messages ) ) {
			$order_note = $this->campaign_id_to_name[ $campaign['id'] ] . ' - WhatsApp notification failed to send to ' . implode( ', ', $error_messages );
			$order->add_order_note( $order_note );
		}
	}

	/**
	 * Send WhatsApp notification to admin
	 *
	 * @since 4.1.2
	 * @param WC_Order $order Order object
	 * @param array $campaign Campaign settings
	 */
	private function send_admin_order_notification( $order, $campaign ) {
		// Check if adminConfig exists
		if ( ! isset( $campaign['adminConfig'] ) ) {
			return;
		}

		// Check if admin notification is enabled
		if ( ! isset( $campaign['adminConfig']['enabled'] ) || ! $campaign['adminConfig']['enabled'] ) {
			return;
		}

		// Check if template name, language and recipients are set
		if ( empty( $campaign['adminConfig']['templateName'] ) ||
		empty( $campaign['adminConfig']['templateLanguage'] ) ||
		empty( $campaign['adminConfig']['recipients'] ) ) {
			return;
		}

		// Get admin phone numbers
		$recipients = explode( ',', $campaign['adminConfig']['recipients'] );
		if ( empty( $recipients ) ) {
			return;
		}
		// format phone numbers
		$recipients = array_map(
			function ( $recipient ) {
				return WhatsappHelper::format_phone_number( $recipient );
			},
			$recipients
		);

		// filter out empty phone numbers
		$recipients = array_filter(
			$recipients,
			function ( $recipient ) {
				return ! empty( $recipient );
			}
		);

		// remove duplicates
		$recipients = array_unique( $recipients );

		// if recipients is empty, return
		if ( empty( $recipients ) ) {
			return;
		}

		// Get replacement variables
		$replacements = WhatsappHelper::get_replacement_variables( $order, array() );

		// Format template components
		$components = $this->build_template_message_components( $campaign['adminConfig'], $replacements );

		$success_numbers = array();
		$error_messages = array();
		// Send to each recipient
		foreach ( $recipients as $recipient ) {
			try {
				// Send the message
				$result = $this->whatsapp_cloud_api->send_template_message( $recipient, $campaign['adminConfig']['templateName'], $campaign['adminConfig']['templateLanguage'], $components );
				if ( $result['success'] ) {
					$success_numbers[] = $recipient;
				} else {
					$this->logger->error( 'Failed to send admin order notification to ' . $recipient, $result );
					$error_messages[] = WhatsappHelper::format_whatsapp_error_message( $recipient, $result );
				}
			} catch ( \Exception $e ) {
				$this->logger->error( 'Error sending admin order notification to ' . $recipient, $e );
				$error_messages[] = WhatsappHelper::format_whatsapp_error_message( $recipient, $e );
			}
		}

		// If some numbers were sent, add a note
		if ( ! empty( $success_numbers ) ) {
			$order_note = $this->campaign_id_to_name[ $campaign['id'] ] . ' - WhatsApp notification sent to admin ' . implode( ', ', $success_numbers );
			$order->add_order_note( $order_note );
		}

		// If some numbers were failed, add a note
		if ( ! empty( $error_messages ) ) {
			$order_note = $this->campaign_id_to_name[ $campaign['id'] ] . ' - WhatsApp notification failed to send to admin ' . implode( ', ', $error_messages );
			$order->add_order_note( $order_note );
		}
	}

	/**
	 * Build template message components for WhatsApp Cloud API
	 *
	 * @since 4.1.2
	 *
	 * @param array $config Automation Campaign user configuration
	 * @param array $replacements Replacement variables
	 * @return array Formatted template components
	 */
	private function build_template_message_components( $config, $replacements ) {
		$components = array();

		// Get header and body variables
		$header_variables = isset( $config['headerVariables'] ) ? $config['headerVariables'] : array();
		$body_variables = isset( $config['bodyVariables'] ) ? $config['bodyVariables'] : array();

		// Determine parameter format (applies to both header and body)
		$parameter_format = WhatsappHelper::get_template_parameter_format( $header_variables, $body_variables );

		// Process header variables if any
		if ( ! empty( $header_variables ) ) {
			$header_params = array();

			foreach ( $header_variables as $variable ) {
				// Replace any placeholders with actual values using the double curly braces format
				$value = WhatsappHelper::replace_placeholders( $variable['value'], $replacements );

				// Create parameter based on format
				$param = array(
					'type' => 'text',
					'text' => $value,
				);

				// For NAMED parameters, add parameter_name
				if ( 'NAMED' === $parameter_format && isset( $variable['key'] ) ) {
					$param['parameter_name'] = $variable['key'];
				}

				$header_params[] = $param;
			}

			if ( ! empty( $header_params ) ) {
				$components[] = array(
					'type'       => 'header',
					'parameters' => $header_params,
				);
			}
		}

		// Process body variables if any
		if ( ! empty( $body_variables ) ) {
			$body_params = array();

			foreach ( $body_variables as $variable ) {
				// Replace any placeholders with actual values using the double curly braces format
				$value = WhatsappHelper::replace_placeholders( $variable['value'], $replacements );

				// Create parameter based on format
				$param = array(
					'type' => 'text',
					'text' => $value,
				);

				// For NAMED parameters, add parameter_name
				if ( 'NAMED' === $parameter_format && isset( $variable['key'] ) ) {
					$param['parameter_name'] = $variable['key'];
				}

				$body_params[] = $param;
			}

			if ( ! empty( $body_params ) ) {
				$components[] = array(
					'type'       => 'body',
					'parameters' => $body_params,
				);
			}
		}

		return $components;
	}

	/**
	 * Send notification for new order
	 *
	 * @since 4.1.2
	 *
	 * @param int $order_id Order ID
	 */
	public function send_new_order_notification( $order_id ) {
		$this->send_order_status_notification( $order_id, 'order_placed' );
	}

	/**
	 * Send notification for processing order
	 *
	 * @since 4.1.2
	 *
	 * @param int $order_id Order ID
	 */
	public function send_processing_order_notification( $order_id ) {
		$this->send_order_status_notification( $order_id, 'order_processing' );
	}

	/**
	 * Send notification for on-hold order
	 *
	 * @since 4.1.2
	 *
	 * @param int $order_id Order ID
	 */
	public function send_on_hold_order_notification( $order_id ) {
		$this->send_order_status_notification( $order_id, 'order_on_hold' );
	}

	/**
	 * Send notification for completed order
	 *
	 * @since 4.1.2
	 *
	 * @param int $order_id Order ID
	 * @return void
	 */
	public function send_completed_order_notification( $order_id ) {
		$this->send_order_status_notification( $order_id, 'order_completed' );
	}

	/**
	 * Send notification for cancelled order
	 *
	 * @since 4.1.2
	 *
	 * @param int $order_id Order ID
	 * @return void
	 */
	public function send_cancelled_order_notification( $order_id ) {
		$this->send_order_status_notification( $order_id, 'order_cancelled' );
	}

	/**
	 * Send notification for failed order
	 *
	 * @since 4.1.2
	 *
	 * @param int $order_id Order ID
	 * @return void
	 */
	public function send_failed_order_notification( $order_id ) {
		$this->send_order_status_notification( $order_id, 'order_failed' );
	}

	/**
	 * Send notification for refunded order
	 *
	 * @since 4.1.2
	 *
	 * @param int $order_id Order ID
	 * @return void
	 */
	public function send_refunded_order_notification( $order_id ) {
		$this->send_order_status_notification( $order_id, 'order_refunded' );
	}

	/**
	 * Send order status notification based on campaign config
	 *
	 * @since 4.1.2
	 *
	 * @param int $order_id Order ID
	 * @param string $campaign_id Campaign identifier
	 * @return void
	 */
	private function send_order_status_notification( $order_id, $campaign_id ) {
		try {
			if ( ! WhatsappHelper::is_valid_whatsapp_credentials( $this->settings ) ) {
				return;
			}

			// Check if campaign exists
			if ( empty( $campaign_id ) || ! isset( $this->campaigns[ $campaign_id ] ) ) {
				return;
			}

			$campaign = $this->campaigns[ $campaign_id ];

			// If campaign is not enabled, return
			if ( ! isset( $campaign['enabled'] ) || ! $campaign['enabled'] ) {
				return;
			}

			// Get order object
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return;
			}

			// Send customer and admin notifications based on campaign settings
			try {
				$this->send_customer_order_notification( $order, $campaign );
			} catch ( \Exception $e ) {
				$this->logger->error( 'Error sending customer order notification ', $e );
			}
			try {
				$this->send_admin_order_notification( $order, $campaign );
			} catch ( \Exception $e ) {
				$this->logger->error( 'Error sending admin order notification ', $e );
			}
		} catch ( \Exception $e ) {
			$this->logger->error( 'Error sending order status notification ', $e );
		}
	}

	/**
	 * Send notification for abandoned cart
	 *
	 * @since 4.1.2
	 *
	 * @param \Pushengage\Integrations\WooCommerce\Whatsapp\CartAbandonment\AbandonedCart $cart Abandoned cart object
	 * @return void
	 */
	public function send_abandoned_cart_notification( $cart ) {
		try {
			if ( ! WhatsappHelper::is_valid_whatsapp_credentials( $this->settings ) ) {
				return;
			}

			// Check if abandoned cart campaign exists
			if ( ! isset( $this->campaigns['cart_abandoned'] ) ) {
				return;
			}

			$campaign = $this->campaigns['cart_abandoned'];

			// If campaign is not enabled, return
			if ( ! isset( $campaign['enabled'] ) || ! $campaign['enabled'] ) {
				return;
			}

			// If customer config is not enabled, return
			if ( ! isset( $campaign['customerConfig'] ) || ! isset( $campaign['customerConfig']['enabled'] ) || ! $campaign['customerConfig']['enabled'] ) {
				return;
			}
			// Get template info
			$template_name = $campaign['customerConfig']['templateName'];
			$template_language = $campaign['customerConfig']['templateLanguage'];
			$recipient_type = isset( $campaign['customerConfig']['recipientType'] ) ? $campaign['customerConfig']['recipientType'] : 'billing';

			// If template name or language is not set, return
			if ( empty( $template_name ) || empty( $template_language ) ) {
				return;
			}

			if ( empty( $cart->get_cart_items() ) ) {
				return;
			}

			if ( ! $cart->has_valid_phone_number() ) {
				return;
			}

			$phone_numbers = $cart->get_phone_numbers( $recipient_type );
			if ( empty( $phone_numbers ) ) {
				return;
			}

			$replacements = WhatsappHelper::get_replacement_variables( array(), $cart );
			$components = $this->build_template_message_components( $campaign['customerConfig'], $replacements );

			// Send to each recipient
			$success_numbers = array();
			foreach ( $phone_numbers as $recipient ) {
				try {
					// Send the message
					$result = $this->whatsapp_cloud_api->send_template_message( $recipient, $template_name, $template_language, $components );
					if ( $result['success'] ) {
						$success_numbers[] = $recipient;
					} else {
						$this->logger->error( 'Failed to send abandoned cart notification to ' . $recipient, $result );
					}
				} catch ( \Exception $e ) {
					$this->logger->error( 'Error sending abandoned cart notification to ' . $recipient, $e );
					$error_messages[] = WhatsappHelper::format_whatsapp_error_message( $recipient, $e );
				}
			}

			// Notification was sent successfully, mark the cart as notified
			if ( ! empty( $success_numbers ) ) {
				$cart->mark_as_notified();
			}
		} catch ( \Exception $e ) {
			$this->logger->fatal( 'Error sending abandoned cart notification ', $e );
		}
	}
}

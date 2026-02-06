<?php

namespace Pushengage\Integrations\WooCommerce\Whatsapp\CartAbandonment;

/**
 * Installer Class for cart abandonment tracking.
 *
 * @since 4.1.2
 */
class Installer {
	/**
	 * Table name for cart abandonment tracking.
	 *
	 * @since 4.1.2
	 */
	private $abandoned_carts_table_name;

	/**
	 * The single instance of the class.
	 *
	 * @since 4.1.2
	 * @var Installer
	 */
	private static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 4.1.2
	 */
	private function __construct() {
		global $wpdb;
		$this->abandoned_carts_table_name = $wpdb->prefix . 'pushengage_wc_abandoned_carts';
	}

	/**
	 * Get the singleton instance of the class.
	 *
	 * @since 4.1.2
	 * @return Installer
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Check if the table exists.
	 *
	 * @since 4.1.2
	 * @return bool
	 */
	public function table_exists( $table_name ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		) === $table_name;
	}

	/**
	 * Install.
	 *
	 * @since 4.1.2
	 */
	public function install() {
		if ( ! $this->table_exists( $this->abandoned_carts_table_name ) ) {
			$this->create_tables();
		}
	}

	/**
	 * Create tables.
	 *
	 * @since 4.1.2
	 */
	private function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->abandoned_carts_table_name} (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) DEFAULT NULL,
			cart_token VARCHAR(20) DEFAULT NULL,
			cart_total DECIMAL(10,2),
			cart_contents longtext NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			notified_at datetime DEFAULT NULL,
			recovered_at datetime DEFAULT NULL,
			notified_count INT(10) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY cart_token (cart_token),
			KEY notified_at (notified_at),
			KEY recovered_at (recovered_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}

<?php
/**
 * Logger class for WordPress plugin error logging
 *
 * @package PushEngage
 * @since 4.1.2
 */

namespace PushEngage;

use Pushengage\Utils\Options;
use Pushengage\Utils\ArrayHelper;

/**
 * Logger class for handling error messages with different log levels.
 *
 * It will write the log to the custom log file if WP_PUSHENGAGE_DEBUG is
 * enabled and to the WordPress debug log if WP_DEBUG is enabled.
 *
 * NOTE:
 * Do not log sensitive data to this logger as the log file is not
 * protected. The log file can be accessed by anyone via browser.
 *
 * A user should only enable the file logging if they are sure that the
 * log file is not accessible by anyone or asked by the PushEngage support
 * team. They should disable the file logging after the issue is resolved
 * and delete the log files.
 *
 *
 * @since 4.1.2
 */
class Logger {

	/**
	 * Singleton instance
	 *
	 * @var Logger
	 */
	private static $instance = null;

	/**
	 * Log levels constants
	 */
	const LOG_LEVEL_DEBUG   = 'debug';
	const LOG_LEVEL_INFO    = 'info';
	const LOG_LEVEL_WARNING = 'warning';
	const LOG_LEVEL_ERROR   = 'error';
	const LOG_LEVEL_FATAL   = 'fatal';

	private $log_levels = array(
		self::LOG_LEVEL_DEBUG   => 0,
		self::LOG_LEVEL_INFO    => 1,
		self::LOG_LEVEL_WARNING => 2,
		self::LOG_LEVEL_ERROR   => 3,
		self::LOG_LEVEL_FATAL   => 4,
	);

	/**
	 * Maximum log file size in bytes (5MB)
	 */
	const MAX_LOG_FILE_SIZE = 5242880; // 5MB in bytes

	/**
	 * Maximum number of backup log files to keep
	 */
	const MAX_BACKUP_FILES = 5;

	/**
	 * Plugin name for logging context
	 *
	 * @var string
	 */
	private $plugin_name = 'PushEngage';

	/**
	 * Minimum log level to record
	 *
	 * @var string
	 */
	private $min_log_level = self::LOG_LEVEL_ERROR;

	/**
	 * Whether logging to custom log file is disabled
	 *
	 * @var bool
	 */
	private $enable_custom_log = false;

	/**
	 * Whether deleting log files is in progress
	 *
	 * @var bool
	 */
	private $is_deleting_log_files = false;

	/**
	 * Whether custom log files can be written
	 *
	 * @var bool
	 */
	private $can_write_custom_log = false;

	/**
	 * Private constructor to prevent direct instantiation
	 *
	 * @since 4.1.2
	 */
	private function __construct() {

		// Get debug settings from site settings.
		$site_settings = Options::get_site_settings();
		$debug_enabled = ArrayHelper::get( $site_settings, 'misc.enableDebugMode', false );
		$debug_level   = ArrayHelper::get( $site_settings, 'misc.debugLevel', self::LOG_LEVEL_DEBUG );

		// Set minimum log level.
		if ( $this->is_valid_log_level( $debug_level ) ) {
			$this->min_log_level = $debug_level;
		}

		// Set enable custom log.
		$this->enable_custom_log = (bool) $debug_enabled;

		// If logging to custom log file is enabled, then initialize log
		// directory and rotate log if needed.
		if ( $this->enable_custom_log ) {
			// Initialize log directory.
			$this->initialize_log_directory();

			// Rotate log if custom logging is available
			if ( $this->can_write_custom_log ) {
				$this->rotate_log_if_needed();
			}
		}
	}

	/**
	 * Get singleton instance
	 *
	 * @since 4.1.2
	 * @return Logger
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Prevent cloning of the instance
	 *
	 * @since 4.1.2
	 * @return void
	 */
	private function __clone() {
		// Prevent cloning.
	}

	/**
	 * Prevent unserializing of the instance
	 *
	 * @since 4.1.2
	 * @return void
	 */
	public function __wakeup() {
		// Prevent unserializing.
	}

	/**
	 * Log a debug message
	 *
	 * @since 4.1.2
	 * @param string $message The message to log.
	 * @param \Exception|\WP_Error|array $context Additional context data
	 * @return void
	 */
	public function debug( $message, $context = array() ) {
		$this->log( self::LOG_LEVEL_DEBUG, $message, $context );
	}

	/**
	 * Log an info message
	 *
	 * @since 4.1.2
	 * @param string $message The message to log.
	 * @param \Exception|\WP_Error|array $context Additional context data
	 * @return void
	 */
	public function info( $message, $context = array() ) {
		$this->log( self::LOG_LEVEL_INFO, $message, $context );
	}

	/**
	 * Log a warning message
	 *
	 * @since 4.1.2
	 * @param string $message The message to log.
	 * @param \Exception|\WP_Error|array $context Additional context data
	 * @return void
	 */
	public function warning( $message, $context = array() ) {
		$this->log( self::LOG_LEVEL_WARNING, $message, $context );
	}

	/**
	 * Log an error message
	 *
	 * @since 4.1.2
	 * @param string $message The message to log.
	 * @param \Exception|\WP_Error|array $context Additional context data
	 * @return void
	 */
	public function error( $message, $context = array() ) {
		$this->log( self::LOG_LEVEL_ERROR, $message, $context );
	}

	/**
	 * Log a fatal error message
	 *
	 * @since 4.1.2
	 * @param string $message The message to log.
	 * @param \Exception|\WP_Error|array $context Additional context data
	 * @return void
	 */
	public function fatal( $message, $context = array() ) {
		$this->log( self::LOG_LEVEL_FATAL, $message, $context );
	}

	/**
	 * Main logging method
	 *
	 * @since 4.1.2
	 * @param string $level   The log level.
	 * @param string $message The message to log.
	 * @param \Exception|\WP_Error|array $context Additional context data
	 * @return void
	 */
	private function log( $level, $message, $context = array() ) {
		if ( ! $this->should_log( $level ) ) {
			return;
		}

		$log_entry = $this->format_log_entry( $message, $context );
		$this->write_log( $level, $log_entry );
	}

	/**
	 * Check if the log level should be recorded
	 *
	 * @since 4.1.2
	 * @param string $level The log level to check.
	 * @return bool
	 */
	private function should_log( $level ) {
		// If custom log is disabled and WP_DEBUG is also not enabled, then don't log.
		if ( ! $this->enable_custom_log && ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			return false;
		}

		$current_level = $this->log_levels[ $level ];
		$min_level     = $this->log_levels[ $this->min_log_level ];

		return $current_level >= $min_level;
	}

	/**
	 * Format the log entry
	 *
	 * @since 4.1.2
	 * @param string $message The message to log.
	 * @param \Exception|\WP_Error|array $context Additional context data
	 * @return string
	 */
	private function format_log_entry( $message, $context = array() ) {
		$log_entry = sanitize_text_field( $message );
		// Handle context based on its type
		if ( ! empty( $context ) ) {
			if ( $context instanceof \Exception ) {
				$log_entry .= $this->format_exception_context( $context );
			} elseif ( is_wp_error( $context ) ) {
				$log_entry .= $this->format_wp_error_context( $context );
			} elseif ( is_array( $context ) ) {
				$log_entry .= $this->format_array_context( $context );
			}
		}

		return $log_entry;
	}

	/**
	 * Format exception context for logging
	 *
	 * @since 4.1.2
	 * @param \Exception $exception The exception object.
	 * @return string
	 */
	private function format_exception_context( $exception ) {
		$context_data = array(
			'exception_class' => get_class( $exception ),
			'message'         => $exception->getMessage(),
			'code'            => $exception->getCode(),
			'file'            => $exception->getFile(),
			'line'            => $exception->getLine(),
			// 'trace'           => $exception->getTraceAsString(),
		);

		// Add previous exception if exists
		if ( $exception->getPrevious() ) {
			$context_data['previous_exception'] = array(
				'class'   => get_class( $exception->getPrevious() ),
				'message' => $exception->getPrevious()->getMessage(),
				'code'    => $exception->getPrevious()->getCode(),
			);
		}

		return ' Exception: ' . wp_json_encode( $context_data );
	}

	/**
	 * Format WP_Error context for logging
	 *
	 * @since 4.1.2
	 * @param \WP_Error $wp_error The WP_Error object.
	 * @return string
	 */
	private function format_wp_error_context( $wp_error ) {
		$context_data = array(
			'error_code'    => $wp_error->get_error_code(),
			'error_message' => $wp_error->get_error_message(),
			'error_data'    => $wp_error->get_error_data(),
		);

		// Get all error codes and messages
		$error_codes = $wp_error->get_error_codes();
		if ( count( $error_codes ) > 1 ) {
			$context_data['all_errors'] = array();
			foreach ( $error_codes as $code ) {
				$context_data['all_errors'][ $code ] = array(
					'message' => $wp_error->get_error_message( $code ),
					'data'    => $wp_error->get_error_data( $code ),
				);
			}
		}

		return ' WP_Error: ' . wp_json_encode( $context_data );
	}

	/**
	 * Format array context for logging
	 *
	 * @since 4.1.2
	 * @param array $context The context array.
	 * @return string
	 */
	private function format_array_context( $context ) {
		$context_json = wp_json_encode( $context );

		if ( false !== $context_json ) {
			return ' Context: ' . $context_json;
		}
		return '';
	}

	/**
	 * Write the log entry to the appropriate destination
	 *
	 * @since 4.1.2
	 * @param string $log_entry The formatted log entry.
	 * @return void
	 */
	private function write_log( $level, $log_entry ) {
		$level = strtoupper( $level );

		// Use WordPress debug log if WP_DEBUG is enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$log_entry = sprintf( '[%s] [%s] %s', $this->plugin_name, $level, $log_entry );
			error_log( $log_entry );
		}

		// only write to custom log file if custom log is enabled.
		if ( $this->enable_custom_log && ! $this->is_deleting_log_files ) {
			$timestamp = current_time( 'Y-m-d H:i:s', true );
			$log_entry = sprintf( '[%s] [%s] %s', $timestamp, $level, $log_entry );
			$log_entry .= PHP_EOL;
			$this->write_to_custom_log_file( $log_entry );
		}
	}

	/**
	 * Create empty index.html file in directory to prevent directory listing
	 *
	 * @since 4.1.2
	 * @param string $directory The directory path where to create index.html.
	 * @return bool True if file was created or already exists, false on failure.
	 */
	private function create_index_html( $directory ) {
		$index_file = $directory . '/index.html';
		if ( ! file_exists( $index_file ) ) {
			$created = file_put_contents( $index_file, '' );
			return false !== $created;
		}
		return true;
	}

	/**
	 * Initialize log directory
	 *
	 * @since 4.1.2
	 * @return void
	 */
	private function initialize_log_directory() {
		$log_dir = $this->get_log_dir_path();

		// Create log directory if it doesn't exist.
		if ( ! file_exists( $log_dir ) ) {
			$created = wp_mkdir_p( $log_dir );
			if ( ! $created ) {
				$this->can_write_custom_log = false;
				return false;
			}
			$this->create_index_html( $log_dir );
		}

		$log_file = $this->get_log_file_path();
		if ( ! file_exists( $log_file ) ) {
			$bytes_written = file_put_contents( $log_file, '' );
			if ( false === $bytes_written ) {
				$this->can_write_custom_log = false;
				return false;
			}
		}
		// Check if log file is writable
		if ( ! is_writable( $log_file ) ) {
			$this->can_write_custom_log = false;
			return false;
		}

		$this->can_write_custom_log = true;
		return true;
	}

	/**
	 * AJAX handler for initializing debug log
	 *
	 * @since 4.1.2
	 * @return void
	 */
	public function ajax_initialize_debug_log() {
		$can_write_custom_log = $this->initialize_log_directory();
		if ( ! $can_write_custom_log ) {
			// translators: %s is the log directory path.
			wp_send_json_error( array( 'message' => sprintf( __( 'Debug log initialization failed. Please check if the log directory is writable. %s', 'pushengage' ), $this->get_log_dir_path() ) ) );
		}
		wp_send_json_success( array( 'message' => 'Debug log initialized' ) );
	}

	/**
	 * Write log entry to custom log file
	 *
	 * @since 4.1.2
	 * @param string $log_entry The formatted log entry.
	 * @return void
	 */
	private function write_to_custom_log_file( $log_entry ) {
		// Check if we can write to custom log files and directory is ready
		if ( $this->enable_custom_log && $this->can_write_custom_log ) {
			$log_file = $this->get_log_file_path();
			file_put_contents( $log_file, $log_entry, FILE_APPEND | LOCK_EX );
		}
	}

	/**
	 * Rotate log file if it exceeds maximum size
	 *
	 * @since 4.1.2
	 * @return void
	 */
	private function rotate_log_if_needed() {
		// Check if log file exists
		$log_file = $this->get_log_file_path();
		if ( ! file_exists( $log_file ) ) {
			return;
		}

		$file_size = filesize( $log_file );
		if ( $file_size < self::MAX_LOG_FILE_SIZE ) {
			return;
		}

		// Move current log file to backup with timestamp
		$timestamp = gmdate( 'Y-m-d\TH-i-s' );
		$backup_file = $this->get_log_dir_path() . '/debug-log-' . $timestamp . '.txt';

		// Try to rename the file, if it fails, just continue
		$renamed = rename( $log_file, $backup_file );
		if ( ! $renamed ) {
			// If rename fails, try to copy and delete
			$copied = copy( $log_file, $backup_file );
			if ( $copied ) {
				file_put_contents( $log_file, '' );
			}
		}

		// Ensure debug-log.txt file exists after rotation
		if ( ! file_exists( $log_file ) ) {
			file_put_contents( $log_file, '' );
		}

		// Delete old backup files
		$this->delete_old_backup_files();
	}

	/**
	 * Delete all log files
	 *
	 * @since 4.1.2
	 * @return void
	 */
	public function delete_all_log_files() {
		$log_dir   = $this->get_log_dir_path();
		$log_files = glob( $log_dir . '/*.txt' );

		$this->is_deleting_log_files = true;

		// Delete all log files.
		foreach ( $log_files as $log_file ) {
			@unlink( $log_file );
		}

		$site_settings = Options::get_site_settings();
		$site_settings['misc']['enableDebugMode'] = false;
		Options::update_site_settings( $site_settings );
	}

	/**
	 * Delete old backup log files
	 *
	 * @since 4.1.2
	 * @return void
	 */
	private function delete_old_backup_files() {
		$log_dir = $this->get_log_dir_path();

		// Get all existing backup files
		$backup_files = glob( $log_dir . '/debug-log-*.txt' );

		// Sort files by modification time (oldest first)
		usort(
			$backup_files,
			function( $a, $b ) {
				return filemtime( $a ) - filemtime( $b );
			}
		);

		// Remove oldest files if we exceed the limit
		$files_to_remove = count( $backup_files ) - self::MAX_BACKUP_FILES + 1;
		if ( $files_to_remove > 0 ) {
			for ( $i = 0; $i < $files_to_remove; $i++ ) {
				if ( isset( $backup_files[ $i ] ) ) {
					// Try to delete, but don't fail if it doesn't work
					@unlink( $backup_files[ $i ] );
				}
			}
		}
	}

	/**
	 * Check if the log level is valid
	 *
	 * @since 4.1.2
	 * @param string $level The log level to check.
	 * @return bool
	 */
	private function is_valid_log_level( $level ) {
		$valid_levels = array(
			self::LOG_LEVEL_DEBUG,
			self::LOG_LEVEL_INFO,
			self::LOG_LEVEL_WARNING,
			self::LOG_LEVEL_ERROR,
			self::LOG_LEVEL_FATAL,
		);
		return in_array( $level, $valid_levels, true );
	}

	/**
	 * Get log directory path
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_log_dir_path() {
		$upload_dir = wp_upload_dir();
		$log_dir    = $upload_dir['basedir'] . '/pushengage/logs';
		return $log_dir;
	}

	/**
	 * Get log file path
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_log_file_path() {
		return $this->get_log_dir_path() . '/debug-log.txt';
	}

	/**
	 * Get log file size in bytes
	 *
	 * @since 4.1.2
	 * @return int
	 */
	public function get_log_file_size() {
		$log_file = $this->get_log_file_path();

		if ( ! file_exists( $log_file ) ) {
			return 0;
		}

		return filesize( $log_file );
	}

	/**
	 * Is log file writable
	 *
	 * @since 4.1.2
	 * @return bool
	 */
	public function is_log_file_writable() {
		return $this->can_write_custom_log;
	}

	/**
	 * Get all log files with their details
	 *
	 * @since 4.1.2
	 * @return array
	 */
	public function get_log_files() {
		$log_dir = $this->get_log_dir_path();
		$files   = array();

		if ( ! file_exists( $log_dir ) ) {
			return $files;
		}

		$log_files = glob( $log_dir . '/*.txt' );

		foreach ( $log_files as $log_file ) {
			$file_name = basename( $log_file );
			$file_size = filesize( $log_file );
			$file_time = filemtime( $log_file );

			$files[] = array(
				'name'          => $file_name,
				'size'          => $file_size,
				'size_human'    => $this->format_file_size( $file_size ),
				'created'       => $file_time,
				'created_human' => $this->format_date( $file_time ),
				'url'           => $this->get_log_file_url( $file_name ),
			);
		}

		// Sort files by creation time (newest first)
		usort(
			$files,
			function( $a, $b ) {
				return $b['created'] - $a['created'];
			}
		);

		return $files;
	}

	/**
	 * Delete a specific log file
	 *
	 * @since 4.1.2
	 * @param string $file_name The name of the file to delete.
	 * @return bool
	 */
	public function delete_log_file( $file_name ) {
		$log_dir = $this->get_log_dir_path();
		$file_path = $log_dir . '/' . $file_name;

		// Validate file name to prevent directory traversal
		if ( ! $this->is_valid_log_file_name( $file_name ) ) {
			return false;
		}

		// Check if file exists and is within the log directory
		if ( ! file_exists( $file_path ) || ! is_file( $file_path ) ) {
			return false;
		}

		// Ensure the file is within the log directory
		$real_file_path = realpath( $file_path );
		$real_log_dir = realpath( $log_dir );

		if ( false === $real_file_path || false === $real_log_dir || 0 !== strpos( $real_file_path, $real_log_dir ) ) {
			return false;
		}

		return @unlink( $file_path );
	}

	/**
	 * Get content of a specific log file
	 *
	 * @since 4.1.2
	 * @param string $file_name The name of the file to read.
	 * @return array|false
	 */
	public function get_log_file_content( $file_name ) {
		$log_dir = $this->get_log_dir_path();
		$file_path = $log_dir . '/' . $file_name;

		// Validate file name to prevent directory traversal
		if ( ! $this->is_valid_log_file_name( $file_name ) ) {
			return false;
		}

		// Check if file exists and is within the log directory
		if ( ! file_exists( $file_path ) || ! is_file( $file_path ) ) {
			return false;
		}

		// Ensure the file is within the log directory
		$real_file_path = realpath( $file_path );
		$real_log_dir = realpath( $log_dir );

		if ( false === $real_file_path || false === $real_log_dir || 0 !== strpos( $real_file_path, $real_log_dir ) ) {
			return false;
		}

		$file_size = filesize( $file_path );
		$file_time = filemtime( $file_path );
		$content = file_get_contents( $file_path );

		if ( false === $content ) {
			return false;
		}

		return array(
			'name'     => $file_name,
			'size'     => $file_size,
			'size_human' => $this->format_file_size( $file_size ),
			'created'  => $file_time,
			'created_human' => $this->format_date( $file_time ),
			'content'  => $content,
			'url'      => $this->get_log_file_url( $file_name ),
		);
	}

	/**
	 * Get log file URL for viewing in browser
	 *
	 * @since 4.1.2
	 * @param string $file_name The name of the file.
	 * @return string
	 */
	private function get_log_file_url( $file_name ) {
		$upload_dir = wp_upload_dir();
		return $upload_dir['baseurl'] . '/pushengage/logs/' . $file_name;
	}

	/**
	 * Format file size in human readable format
	 *
	 * @since 4.1.2
	 * @param int $bytes File size in bytes.
	 * @return string
	 */
	private function format_file_size( $bytes ) {
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		$bytes = max( $bytes, 0 );
		$pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow = min( $pow, count( $units ) - 1 );

		$bytes /= pow( 1024, $pow );

		return round( $bytes, 2 ) . ' ' . $units[ $pow ];
	}

	/**
	 * Format date in human readable format
	 *
	 * @since 4.1.2
	 * @param int $timestamp Unix timestamp.
	 * @return string
	 */
	private function format_date( $timestamp ) {
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}

	/**
	 * Validate log file name to prevent directory traversal
	 *
	 * @since 4.1.2
	 * @param string $file_name The file name to validate.
	 * @return bool
	 */
	private function is_valid_log_file_name( $file_name ) {
		// Check for directory traversal attempts
		if ( false !== strpos( $file_name, '..' ) || false !== strpos( $file_name, '/' ) || false !== strpos( $file_name, '\\' ) ) {
			return false;
		}

		// Check if file name contains only allowed characters
		if ( ! preg_match( '/^[a-zA-Z0-9\-_\.]+\.txt$/', $file_name ) ) {
			return false;
		}

		return true;
	}
}
